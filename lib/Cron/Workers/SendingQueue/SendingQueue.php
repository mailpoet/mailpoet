<?php
namespace MailPoet\Cron\Workers\SendingQueue;

use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Mailer as MailerTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Newsletter as NewsletterTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Statistics as StatisticsTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Subscribers as SubscribersTask;
use MailPoet\Models\SendingQueue as SendingQueueModel;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class SendingQueue {
  public $mta_config;
  public $mta_log;
  private $timer;
  const BATCH_SIZE = 50;
  const STATUS_COMPLETED = 'completed';

  function __construct($timer = false) {
    $this->mta_config = MailerTask::getMailerConfig();
    $this->mta_log = MailerTask::getMailerLog();
    $this->timer = ($timer) ? $timer : microtime(true);
    CronHelper::checkExecutionTimer($this->timer);
  }

  function process() {
    foreach($this->getQueues() as $queue) {
      $newsletter = NewsletterTask::getAndPreProcess($queue->asArray());
      if(!$newsletter) {
        $queue->delete();
        continue;
      }
      if(is_null($queue->newsletter_rendered_body)) {
        $queue->newsletter_rendered_body = json_encode($newsletter['rendered_body']);
        $queue->save();
      }
      $queue->subscribers = SubscribersTask::get($queue->asArray());
      // configure mailer with newsletter data (from/reply-to)
      $mailer = MailerTask::configureMailer($newsletter);
      $processing_method = MailerTask::getProcessingMethod($this->mta_config);
      foreach(array_chunk($queue->subscribers['to_process'], self::BATCH_SIZE) as
              $subscribers_to_process_ids) {
        $subscribers = Subscriber::whereIn('id', $subscribers_to_process_ids)
            ->findArray();
        // if some subscribers weren't found, remove them from the processing list
        if(count($subscribers) !== count($subscribers_to_process_ids)) {
          $queue->subscribers['to_process'] = Subscribers::updateCount(
            Helpers::arrayColumn($subscribers, 'id'),
            $subscribers_to_process_ids,
            $queue->subscribers['to_process']
          );
        }
        if(!count($queue->subscribers['to_process'])) {
          $this->updateQueue($queue);
          continue;
        }
        $queue->subscribers = call_user_func_array(
          array($this, $processing_method),
          array($mailer, $newsletter, $subscribers, $queue)
        );
      }
    }
  }

  function processBulkSubscribers($mailer, $newsletter, $subscribers, $queue) {
    $subscriber_log = array();
    $subscribers_ids = Helpers::arrayColumn($subscribers, 'id');
    foreach($subscribers as $subscriber) {
      // render shortcodes and replace subscriber data in tracked links
      $prepared_newsletters[] =
        NewsletterTask::prepareNewsletterForSending(
          $newsletter,
          $subscriber,
          $queue->asArray()
        );
      if(!$queue->newsletter_rendered_subject) {
        $queue->newsletter_rendered_subject = $prepared_newsletters[0]['subject'];
      }
      // format subscriber name/address according to mailer settings
      $prepared_subscribers[] = MailerTask::prepareSubscriberForSending(
        $mailer,
        $subscriber
      );
    }
    // send
    $result = MailerTask::send($mailer, $prepared_newsletters, $prepared_subscribers);
    if(!$result) {
      // record failed subscribers
      $subscriber_log['failed'] = SubscribersTask::updateFailedList(
        $queue->subscribers['failed'],
        $subscribers_ids
      );
    } else {
      StatisticsTask::updateBulkNewsletterStatistics(
        $subscribers_ids,
        $newsletter['id'],
        $queue->id
      );
      MailerTask::updateMailerLog($this->mta_log);
      $subscriber_log['processed'] = array_merge(
        $queue->subscribers['processed'],
        $subscribers_ids
      );
    }
    // TODO
    //$queue = $this->updateQueue($queue, $subscriber_log);
    MailerTask::checkSendingLimit($this->mta_config, $this->mta_log);
    CronHelper::checkExecutionTimer($this->timer);
    return $queue->subscribers;
  }

 /* function processIndividualSubscriber($mailer, $newsletter, $subscribers, $queue) {
    foreach($subscribers as $subscriber) {
      $this->checkSendingLimit();
      $processed_newsletter = $this->prepareNewsletterForSending($newsletter, $subscriber, $queue);
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
        $queue->subscribers['failed'][] = $subscriber['id'];
      } else {
        $queue->subscribers['processed'][] = $subscriber['id'];
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
  }*/

  function getQueues() {
    return SendingQueueModel::orderByDesc('priority')
      ->whereNull('deleted_at')
      ->whereNull('status')
      ->findResultSet();
  }

  function updateQueue($queue) {
    // TODO
    return;
    $queue = clone($queue);
    $queue->subscribers['to_process'] = array_diff(
      $queue->subscribers['to_process'],
      array_merge(
        $queue->subscribers['processed'],
        $queue->subscribers['failed']
      )
    );
    $queue->subscribers['to_process'] = array_values(
      $queue->subscribers['to_process']
    );
    $queue->count_processed =
      count($queue->subscribers['processed']) + count($queue->subscribers['failed']);
    $queue->count_to_process = count($queue->subscribers['to_process']);
    $queue->count_failed = count($queue->subscribers['failed']);
    $queue->count_total =
      $queue->count_processed + $queue->count_to_process;
    if(!$queue->count_to_process) {
      $queue->processed_at = current_time('mysql');
      $queue->status = self::STATUS_COMPLETED;
    }
    $queue->subscribers = serialize((array) $queue->subscribers);
    $queue->save();
  }
}