<?php
namespace MailPoet\Cron\Workers;

use MailPoet\Cron\CronHelper;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Newsletter;
use MailPoet\Models\Setting;
use MailPoet\Models\StatisticsNewsletters;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Links\Links;
use MailPoet\Newsletter\Renderer\PostProcess\OpenTracking;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class SendingQueue {
  public $mta_config;
  public $mta_log;
  public $processing_method;
  private $timer;
  const BATCH_SIZE = 50;
  const DIVIDER = '***MailPoet***';

  function __construct($timer = false) {
    $this->mta_config = $this->getMailerConfig();
    $this->mta_log = $this->getMailerLog();
    $this->processing_method = ($this->mta_config['method'] === 'MailPoet') ?
      'processBulkSubscribers' :
      'processIndividualSubscriber';
    $this->timer = ($timer) ? $timer : microtime(true);
    CronHelper::checkExecutionTimer($this->timer);
  }

  function process() {
    foreach($this->getQueues() as $queue) {
      $newsletter = Newsletter::findOne($queue->newsletter_id);
      if(!$newsletter) {
        $queue->delete();
        continue;
      }
      $newsletter = $newsletter->asArray();
      $newsletter['body'] = $this->getOrRenderNewsletterBody($queue, $newsletter);
      $queue->subscribers = (object) unserialize($queue->subscribers);
      if(!isset($queue->subscribers->processed)) {
        $queue->subscribers->processed = array();
      }
      if(!isset($queue->subscribers->failed)) {
        $queue->subscribers->failed = array();
      }
      $mailer = $this->configureMailer($newsletter);
      foreach(array_chunk($queue->subscribers->to_process, self::BATCH_SIZE) as
              $subscribers_ids) {
        $subscribers = Subscriber::whereIn('id', $subscribers_ids)
          ->findArray();
        if (count($subscribers_ids) !== count($subscribers)) {
          $queue->subscribers->to_process = $this->recalculateSubscriberCount(
            Helpers::arrayColumn($subscribers, 'id'),
            $subscribers_ids,
            $queue->subscribers->to_process
          );
        }
        if (!count($queue->subscribers->to_process)) {
          $this->updateQueue($queue);
          continue;
        }
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
      }
    }
  }

  function getOrRenderNewsletterBody($queue, $newsletter) {
    // check if newsletter has been rendered, in which case return its contents
    // or render and save for future reuse
    if($queue->newsletter_rendered_body === null) {
      if((boolean) Setting::getValue('tracking.enabled')) {
        // insert tracking code
        add_filter('mailpoet_rendering_post_process', function($template) {
          return OpenTracking::process($template);
        });
        // render newsletter
        list($rendered_newsletter, $queue->newsletter_rendered_body_hash) =
          $this->renderNewsletter($newsletter);
        // extract and replace links
        $processed_newsletter = $this->processLinks(
          $this->joinObject($rendered_newsletter),
          $newsletter['id'],
          $queue->id
        );
        list($newsletter['body']['html'], $newsletter['body']['text']) =
          $this->splitObject($processed_newsletter);
      }
      else {
        // render newsletter
        list($newsletter['body'], $queue->newsletter_rendered_body_hash) =
          $this->renderNewsletter($newsletter);
      }
      $queue->newsletter_rendered_body = json_encode($newsletter['body']);
      $queue->save();
    } else {
      $newsletter['body'] = json_decode($queue->newsletter_rendered_body);
    }
    return (array) $newsletter['body'];
  }

  function processBulkSubscribers($mailer, $newsletter, $subscribers, $queue) {
    foreach($subscribers as $subscriber) {
      $processed_newsletters[] =
        $this->processNewsletter($newsletter, $subscriber, $queue);
      if(!$queue->newsletter_rendered_subject) {
        $queue->newsletter_rendered_subject = $processed_newsletters[0]['subject'];
      }
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
        array_map(function($data) use ($newsletter, $subscribers_ids, $queue) {
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
    CronHelper::checkExecutionTimer($this->timer);
    return $queue->subscribers;
  }

  function processIndividualSubscriber($mailer, $newsletter, $subscribers, $queue) {
    foreach($subscribers as $subscriber) {
      $this->checkSendingLimit();
      $processed_newsletter = $this->processNewsletter($newsletter, $subscriber, $queue);
      if(!$queue->newsletter_rendered_subject) {
        $queue->newsletter_rendered_subject = $processed_newsletter['subject'];
      }
      $transformed_subscriber = $mailer->transformSubscriber($subscriber);
      $result = $this->sendNewsletter(
        $mailer,
        $processed_newsletter,
        $transformed_subscriber
      );
      if(!$result) {
        $queue->subscribers->failed[] = $subscriber['id'];
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
      CronHelper::checkExecutionTimer($this->timer);
    }
    return $queue->subscribers;
  }

  function updateNewsletterStatistics($data) {
    return StatisticsNewsletters::createMultiple($data);
  }

  function renderNewsletter($newsletter) {
    $renderer = new Renderer($newsletter);
    $rendered_newsletter = $renderer->render();
    $rendered_newsletter_hash = md5($rendered_newsletter['text']);
    return array($rendered_newsletter, $rendered_newsletter_hash);
  }

  function processLinks($content, $newsletter_id, $queue_id) {
    list($content, $processed_links) =
      Links::process(
        $content,
        $links = false,
        $process_link_shortcodes = true,
        $queue_id
      );
    Links::save($processed_links, $newsletter_id, $queue_id);
    return $content;
  }

  function processNewsletter($newsletter, $subscriber = false, $queue) {
    $data_for_shortcodes = array(
      $newsletter['subject'],
      $newsletter['body']['html'],
      $newsletter['body']['text']
    );
    $processed_newsletter = $this->replaceShortcodes(
      $newsletter,
      $subscriber,
      $queue,
      $this->joinObject($data_for_shortcodes)
    );
    if((boolean) Setting::getValue('tracking.enabled')) {
      $processed_newsletter = Links::replaceSubscriberData(
        $newsletter['id'],
        $subscriber['id'],
        $queue->id,
        $processed_newsletter
      );
    }
    list($newsletter['subject'],
      $newsletter['body']['html'],
      $newsletter['body']['text']
      ) = $this->splitObject($processed_newsletter);
    return $newsletter;
  }

  function replaceShortcodes($newsletter, $subscriber, $queue, $body) {
    $shortcodes = new Shortcodes(
      $newsletter,
      $subscriber,
      $queue
    );
    return $shortcodes->replace($body);
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
    $queue->subscribers->to_process = array_values(
      $queue->subscribers->to_process
    );
    $queue->count_processed =
      count($queue->subscribers->processed) + count($queue->subscribers->failed);
    $queue->count_to_process = count($queue->subscribers->to_process);
    $queue->count_failed = count($queue->subscribers->failed);
    $queue->count_total =
      $queue->count_processed + $queue->count_to_process;
    if(!$queue->count_to_process) {
      $queue->processed_at = current_time('mysql');
      $queue->status = 'completed';
    }
    $queue->subscribers = serialize((array) $queue->subscribers);
    $queue->save();
  }

  function updateMailerLog() {
    $this->mta_log['sent']++;
    return Setting::setValue('mta_log', $this->mta_log);
  }

  function getMailerConfig() {
    $mta_config = Setting::getValue('mta');
    if(!$mta_config) {
      throw new \Exception(__('Mailer is not configured.'));
    }
    return $mta_config;
  }

  function getMailerLog() {
    $mta_log = Setting::getValue('mta_log');
    if(!$mta_log) {
      $mta_log = array(
        'sent' => 0,
        'started' => time()
      );
      Setting::setValue('mta_log', $mta_log);
    }
    return $mta_log;
  }

  function checkSendingLimit() {
    $frequency_interval = (int) $this->mta_config['frequency']['interval'] * 60;
    $frequency_limit = (int) $this->mta_config['frequency']['emails'];
    $elapsed_time = time() - (int) $this->mta_log['started'];
    if($this->mta_log['sent'] === $frequency_limit &&
      $elapsed_time <= $frequency_interval
    ) {
      throw new \Exception(__('Sending frequency limit reached.'));
    }
    if($elapsed_time > $frequency_interval) {
      $this->mta_log = array(
        'sent' => 0,
        'started' => time()
      );
      Setting::setValue('mta_log', $this->mta_log);
    }
    return;
  }

  function recalculateSubscriberCount(
    $found_subscriber, $existing_subscribers, $subscribers_to_process) {
    $subscibers_to_exclude = array_diff($existing_subscribers, $found_subscriber);
    return array_diff($subscribers_to_process, $subscibers_to_exclude);
  }

  private function joinObject($object = array()) {
    return implode(self::DIVIDER, $object);
  }

  private function splitObject($object = array()) {
    return explode(self::DIVIDER, $object);
  }
}