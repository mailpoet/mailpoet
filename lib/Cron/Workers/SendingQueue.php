<?php
namespace MailPoet\Cron\Workers;

use MailPoet\Cron\CronHelper;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterStatistics;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class SendingQueue {
  public $mailer_config;
  public $mailer_log;
  public $processing_method;
  private $timer;
  const batch_size = 50;

  function __construct($timer = false) {
    $this->mailer_config = $this->getMailerConfig();
    $this->mailer_log = $this->getMailerLog();
    $this->processing_method = ($this->mailer_config['method'] === 'MailPoet') ?
      'processBulkSubscribers' :
      'processIndividualSubscriber';
    $this->timer = ($timer) ? $timer : microtime(true);
  }

  function process() {
    foreach($this->getQueues() as $queue) {
      $newsletter = Newsletter::findOne($queue->newsletter_id);
      if(!$newsletter) {
        continue;
      }
      $queue->subscribers = (object) unserialize($queue->subscribers);
      if(!isset($queue->subscribers->processed)) {
        $queue->subscribers->processed = array();
      }
      if(!isset($queue->subscribers->failed)) {
        $queue->subscribers->failed = array();
      }
      $newsletter = $newsletter->asArray();
      $newsletter['body'] = $this->renderNewsletter($newsletter);
      $mailer = $this->configureMailer($newsletter);
      foreach(array_chunk($queue->subscribers->to_process, self::batch_size) as
              $subscribers_ids) {
        $subscribers = Subscriber::whereIn('id', $subscribers_ids)
          ->findArray();
        $queue->subscribers = call_user_func_array(
          array(
            $this,
            $this->processing_method
          ),
          array(
            $mailer,
            $newsletter,
            $subscribers,
            $queue
          )
        );
        die('aaaa');
      }
    }
  }

  function processBulkSubscribers($mailer, $newsletter, $subscribers, $queue) {
    foreach($subscribers as $subscriber) {
      $processed_newsletters[] =
        $this->processNewsletter($newsletter, $subscriber);
      $transformed_subscribers[] =
        $mailer->transformSubscriber($subscriber);
    }
    $result = $this->sendNewsletter(
      $mailer,
      $processed_newsletters,
      $transformed_subscribers
    );
    $subscribers_ids = Helpers::arrayColumn($subscribers, 'id');
    if(!$result) {
      $queue->subscribers->failed = array_merge(
        $queue->subscribers->failed,
        $subscribers_ids
      );
    } else {
      $newsletter_statistics =
        array_map(function ($data) use ($newsletter, $subscribers_ids, $queue) {
          return array(
            $newsletter['id'],
            $subscribers_ids[$data],
            $queue->id
          );
        }, range(0, count($transformed_subscribers) - 1));
      $newsletter_statistics = Helpers::flattenArray($newsletter_statistics);
      $this->updateMailerLog();
      $this->updateNewsletterStatistics($newsletter_statistics);
      $queue->subscribers->processed = array_merge(
        $queue->subscribers->processed,
        $subscribers_ids
      );
    }
    $this->updateQueue($queue);
    $this->checkSendingLimit();
    $this->checkExecutionTimer();
    die('zzz');
    return $queue->subscribers;
  }

  function processIndividualSubscriber($mailer, $newsletter, $subscribers, $queue) {
    foreach($subscribers as $subscriber) {
      $processed_newsletter = $this->processNewsletter($newsletter, $subscriber);
      $transformed_subscriber = $mailer->transformSubscriber($subscriber);
      $result = $this->sendNewsletter(
        $mailer,
        $processed_newsletter,
        $transformed_subscriber
      );
      if(!$result) {
        $queue->subscribers->failed[] = $subscriber['id'];;
      } else {
        $queue->subscribers->processed[] = $subscriber['id'];
        $newsletter_statistics = array(
          $newsletter['id'],
          $subscriber['id'],
          $queue->id
        );
        $this->updateMailerLog();
        $this->updateNewsletterStatistics($newsletter_statistics);
      }
      $this->updateQueue($queue);
      $this->checkSendingLimit();
      $this->checkExecutionTimer();
    }
    return $queue->subscribers;
  }

  function updateNewsletterStatistics($data) {
    return NewsletterStatistics::createMultiple($data);
  }

  function renderNewsletter($newsletter) {
    $renderer = new Renderer($newsletter);
    return $renderer->render();
  }

  function processNewsletter($newsletter, $subscriber = false) {
    $divider = '***MailPoet***';
    $shortcodes = new Shortcodes(
      implode($divider, $newsletter['body']),
      $newsletter,
      $subscriber
    );
    list($newsletter['body']['html'], $newsletter['body']['text']) =
      explode($divider, $shortcodes->replace());
    return $newsletter;
  }

  function sendNewsletter($mailer, $newsletter, $subscriber) {
    return $mailer->mailer_instance->send(
      $newsletter,
      $subscriber
    );
  }

  function configureMailer($newsletter) {
    $sender['address'] = (!empty($newsletter['sender_address'])) ?
      $newsletter['sender_address'] :
      false;
    $sender['name'] = (!empty($newsletter['sender_name'])) ?
      $newsletter['sender_name'] :
      false;
    $reply_to['address'] = (!empty($newsletter['reply_to_address'])) ?
      $newsletter['reply_to_address'] :
      false;
    $reply_to['name'] = (!empty($newsletter['reply_to_name'])) ?
      $newsletter['reply_to_name'] :
      false;
    if(!$sender['address']) {
      $sender = false;
    }
    if(!$reply_to['address']) {
      $reply_to = false;
    }
    $mailer = new Mailer($method = false, $sender, $reply_to);
    return $mailer;
  }

  function checkExecutionTimer() {
    $elapsed_time = microtime(true) - $this->timer;
    if($elapsed_time >= CronHelper::daemon_execution_limit) {
      throw new \Exception(__('Maximum execution time reached.'));
    }
  }

  function getQueues() {
    return \MailPoet\Models\SendingQueue::orderByDesc('priority')
      ->whereNull('deleted_at')
      ->whereNull('status')
      ->findResultSet();
  }

  function updateQueue($queue) {
    $queue = clone($queue);
    $queue->subscribers->to_process = array_diff(
      $queue->subscribers->to_process,
      array_merge(
        $queue->subscribers->processed,
        $queue->subscribers->failed
      )
    );
    $queue->subscribers->to_process = array_values($queue->subscribers->to_process);
    $queue->count_processed =
      count($queue->subscribers->processed) + count($queue->subscribers->failed);
    $queue->count_to_process = count($queue->subscribers->to_process);
    $queue->count_failed = count($queue->subscribers->failed);
    $queue->count_total =
      $queue->count_processed + $queue->count_to_process;
    if(!$queue->count_to_process) {
      $queue->processed_at = date('Y-m-d H:i:s');
      $queue->status = 'completed';
    }
    $queue->subscribers = serialize((array) $queue->subscribers);
    $queue->save();
  }

  function updateMailerLog() {
    $this->mailer_log['sent']++;
    return Setting::setValue('mailer_log', $this->mailer_log);
  }

  function checkSendingLimit() {
    // TODO: enforce sending frequency
  }

  function getMailerConfig() {
    $mailer_config = Setting::getValue('mta');
    if(!$mailer_config) {
      throw new \Exception(__('Mailer is not configured.'));
    }
    return $mailer_config;
  }

  function getMailerLog() {
    $mailer_log = Setting::getValue('mta_log');
    if(!$mailer_log) {
      $mailer_log = array(
        'sent' => 0,
        'started' => time()
      );
    }
    return $mailer_log;
  }
}