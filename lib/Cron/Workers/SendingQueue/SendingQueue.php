<?php
namespace MailPoet\Cron\Workers\SendingQueue;

use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Mailer as MailerTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Newsletter as NewsletterTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Statistics as StatisticsTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Subscribers as SubscribersTask;
use MailPoet\Models\Newsletter as NewsletterModel;
use MailPoet\Models\SendingQueue as SendingQueueModel;
use MailPoet\Models\Subscriber;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class SendingQueue {
  public $mta_config;
  private $timer;
  const BATCH_SIZE = 50;

  function __construct($timer = false) {
    $this->mta_config = MailerTask::getMailerConfig();
    $this->timer = ($timer) ? $timer : microtime(true);
    CronHelper::checkExecutionTimer($this->timer);
  }

  function process() {
    return;
    $mta_log = MailerTask::getMailerLog();
    MailerTask::checkSendingLimit($this->mta_config, $mta_log);
    foreach($this->getQueues() as $queue) {
      // get and pre-process newsletter (render, replace shortcodes/links, etc.)
      $newsletter = NewsletterTask::getAndPreProcess($queue->asArray());
      if(!$newsletter) {
        $queue->delete();
        continue;
      }
      if(is_null($queue->newsletter_rendered_body)) {
        $queue->newsletter_rendered_body = json_encode($newsletter['rendered_body']);
        //$queue->save();
      }
      // get subscribers
      $queue->subscribers = SubscribersTask::get($queue->asArray());
      // configure mailer with newsletter data (from/reply-to)
      $mailer = MailerTask::configureMailer($newsletter);
      // determine if processing is done in bulk or individually
      $processing_method = MailerTask::getProcessingMethod($this->mta_config);
      foreach(array_chunk($queue->subscribers['to_process'], self::BATCH_SIZE)
              as $subscribers_to_process_ids
      ) {
        $found_subscribers = Subscriber::whereIn('id', $subscribers_to_process_ids)
          ->findArray();
        $found_subscribers_ids = Helpers::arrayColumn($found_subscribers, 'id');
        // if some subscribers weren't found, remove them from the processing list
        if(count($found_subscribers_ids) !== count($subscribers_to_process_ids)) {
          $queue->subscribers = SubscribersTask::updateToProcessList(
            $found_subscribers_ids,
            $subscribers_to_process_ids,
            $queue->subscribers
          );
        }
        if(!count($queue->subscribers['to_process'])) {
          $this->updateQueue($queue);
          continue;
        }
        $queue->subscribers = call_user_func_array(
          array(
            $this,
            $processing_method
          ),
          array(
            $mailer,
            $mta_log,
            $newsletter,
            $found_subscribers,
            $queue
          )
        );
      }
    }
  }

  // TODO: merge processBulkSubscribers with processIndividualSubscriber
  function processBulkSubscribers($mailer, $mta_log, $newsletter, $subscribers, $queue) {
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
    $send_result = MailerTask::send($mailer, $prepared_newsletters, $prepared_subscribers);
    if(!$send_result) {
      // update failed/to process list
      $queue->subscribers = SubscribersTask::updateFailedList(
        $subscribers_ids,
        $queue->subscribers
      );
    } else {
      // update processed/to process list
      $queue->subscribers = SubscribersTask::updateProcessedList(
        $subscribers_ids,
        $queue->subscribers
      );
      // log statistics
      StatisticsTask::processAndLogBulkNewsletterStatistics(
        $subscribers_ids,
        $newsletter['id'],
        $queue->id
      );
      // keep track of sent items
      $mta_log = MailerTask::updateMailerLog($mta_log);
    }
    $this->updateQueue($queue);
    MailerTask::checkSendingLimit($this->mta_config, $mta_log);
    CronHelper::checkExecutionTimer($this->timer);
  }

  function processIndividualSubscriber($mailer, $mta_log, $newsletter, $subscribers, $queue) {
    $subscribers_ids = Helpers::arrayColumn($subscribers, 'id');
    foreach($subscribers as $subscriber) {
      // render shortcodes and replace subscriber data in tracked links
      $prepared_newsletter =
        NewsletterTask::prepareNewsletterForSending(
          $newsletter,
          $subscriber,
          $queue->asArray()
        );
      if(!$queue->newsletter_rendered_subject) {
        $queue->newsletter_rendered_subject = $prepared_newsletter['subject'];
      }
      // format subscriber name/address according to mailer settings
      $prepared_subscriber = MailerTask::prepareSubscriberForSending(
        $mailer,
        $subscriber
      );
      $send_result = MailerTask::send($mailer, $prepared_newsletter, $prepared_subscriber);
      if(!$send_result) {
        // update failed/to process list
        $queue->subscribers = SubscribersTask::updateFailedList(
          $subscribers_ids,
          $queue->subscribers
        );
      } else {
        // update processed/to process list
        $queue->subscribers = SubscribersTask::updateProcessedList(
          $subscribers_ids,
          $queue->subscribers
        );
        // log statistics
        StatisticsTask::logStatistics(
          array(
            $newsletter['id'],
            $subscriber['id'],
            $queue->id
          )
        );
        // keep track of sent items
        $mta_log = MailerTask::updateMailerLog($mta_log);
      }
      $queue = $this->updateQueue($queue);
      MailerTask::checkSendingLimit($this->mta_config, $mta_log);
      CronHelper::checkExecutionTimer($this->timer);
    }
  }

  function getQueues() {
    return SendingQueueModel::orderByDesc('priority')
      ->whereNull('deleted_at')
      ->whereNull('status')
      ->findResultSet();
  }

  function updateQueue($queue) {
    $queue->count_processed =
      count($queue->subscribers['processed']) + count($queue->subscribers['failed']);
    $queue->count_to_process = count($queue->subscribers['to_process']);
    $queue->count_failed = count($queue->subscribers['failed']);
    $queue->count_total =
      $queue->count_processed + $queue->count_to_process;
    if(!$queue->count_to_process) {
      $queue->processed_at = current_time('mysql');
      $queue->status = SendingQueueModel::STATUS_COMPLETED;
      // set newsletter status to sent
      $newsletter = NewsletterModel::findOne($queue->newsletter_id);
      // if it's a standard newsletter, update its status
      if($newsletter->type === NewsletterModel::TYPE_STANDARD) {
        $newsletter->setStatus(NewsletterModel::STATUS_SENT);
      }
    }
    $queue->subscribers = serialize((array) $queue->subscribers);
    $queue->save();
    return $queue;
  }
}