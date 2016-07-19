<?php
namespace MailPoet\Cron\Workers\SendingQueue;

use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Mailer as MailerTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Newsletter as NewsletterTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Subscribers as SubscribersTask;
use MailPoet\Models\Newsletter as NewsletterModel;
use MailPoet\Models\SendingQueue as SendingQueueModel;
use MailPoet\Models\StatisticsNewsletters as StatisticsNewslettersModel;
use MailPoet\Models\Subscriber as SubscriberModel;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class SendingQueue {
  public $mailer_task;
  public $newsletter_task;
  private $timer;
  const BATCH_SIZE = 50;

  function __construct($timer = false) {
    $this->mailer_task = new MailerTask();
    $this->newsletter_task = new NewsletterTask();
    $this->timer = ($timer) ? $timer : microtime(true);
  }

  function process() {
    $this->mailer_task->checkSendingLimit();
    foreach(self::getRunningQueues() as $queue) {
      // get and pre-process newsletter (render, replace shortcodes/links, etc.)
      $newsletter = $this->newsletter_task->getAndPreProcess($queue->asArray());
      if(!$newsletter) {
        $queue->delete();
        continue;
      }
      // configure mailer
      $this->mailer_task->configureMailer($newsletter);
      if(is_null($queue->newsletter_rendered_body)) {
        $queue->newsletter_rendered_body = json_encode($newsletter['rendered_body']);
        $queue->save();
      }
      // get subscribers
      $queue->subscribers = $queue->getSubscribers();
      $subscriber_batches = array_chunk(
        $queue->subscribers['to_process'],
        self::BATCH_SIZE
      );
      foreach($subscriber_batches as $subscribers_to_process_ids) {
        $found_subscribers = SubscriberModel::whereIn('id', $subscribers_to_process_ids)
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
        $queue = $this->processQueue(
          $queue,
          $newsletter,
          $found_subscribers
        );
      }
    }
  }

  function processQueue($queue, $newsletter, $subscribers) {
    // determine if processing is done in bulk or individually
    $processing_method = $this->mailer_task->getProcessingMethod();
    $prepared_newsletters = array();
    $prepared_subscribers = array();
    $prepared_subscribers_ids = array();
    $statistics = array();
    foreach($subscribers as $subscriber) {
      // render shortcodes and replace subscriber data in tracked links
      $prepared_newsletters[] =
        $this->newsletter_task->prepareNewsletterForSending(
          $newsletter,
          $subscriber,
          $queue->asArray()
        );
      if(!$queue->newsletter_rendered_subject) {
        $queue->newsletter_rendered_subject = $prepared_newsletters[0]['subject'];
      }
      // format subscriber name/address according to mailer settings
      $prepared_subscribers[] = $this->mailer_task->prepareSubscriberForSending(
        $subscriber
      );
      $prepared_subscribers_ids[] = $subscriber['id'];
      // keep track of values for statistics purposes
      $statistics[] = array(
        'newsletter_id' => $newsletter['id'],
        'subscriber_id' => $subscriber['id'],
        'queue_id' => $queue->id
      );
      if($processing_method === 'individual') {
        $queue = $this->sendNewsletters(
          $queue,
          $prepared_subscribers_ids,
          $prepared_newsletters[0],
          $prepared_subscribers[0],
          $statistics
        );
        $prepared_newsletters = array();
        $prepared_subscribers = array();
        $prepared_subscribers_ids = array();
        $statistics = array();
      }
    }
    if($processing_method === 'bulk') {
      $queue = $this->sendNewsletters(
        $queue,
        $prepared_subscribers_ids,
        $prepared_newsletters,
        $prepared_subscribers,
        $statistics
      );
    }
    return $queue;
  }

  function sendNewsletters(
    $queue, $prepared_subscribers_ids, $prepared_newsletters,
    $prepared_subscribers, $statistics
  ) {
    // send newsletter
    $send_result = $this->mailer_task->send(
      $prepared_newsletters,
      $prepared_subscribers
    );
    if(!$send_result) {
      // update failed/to process list
      $queue->subscribers = SubscribersTask::updateFailedList(
        $prepared_subscribers_ids,
        $queue->subscribers
      );
    } else {
      // update processed/to process list
      $queue->subscribers = SubscribersTask::updateProcessedList(
        $prepared_subscribers_ids,
        $queue->subscribers
      );
      // log statistics
      StatisticsNewslettersModel::createMultiple($statistics);
      // keep track of sent items
      $this->mailer_task->updateMailerLog();
      $subscribers_to_process_count = count($queue->subscribers['to_process']);
    }
    $queue = $this->updateQueue($queue);
    if($subscribers_to_process_count) {
      $this->mailer_task->checkSendingLimit();
    }
    CronHelper::checkExecutionTimer($this->timer);
    return $queue;
  }

  function getQueues() {
    return SendingQueueModel::orderByDesc('priority')
      ->whereNull('deleted_at')
      ->whereNull('status')
      ->findMany();
  }

  static function getRunningQueues() {
    return SendingQueueModel::orderByDesc('priority')
      ->whereNull('deleted_at')
      ->whereNull('status')
      ->findMany();
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
      // if it's a standard or post notificaiton newsletter, update its status to sent
      $newsletter = NewsletterModel::findOne($queue->newsletter_id);
      if($newsletter->type === NewsletterModel::TYPE_STANDARD ||
         $newsletter->type === NewsletterModel::TYPE_NOTIFICATION_HISTORY
      ) {
        $newsletter->setStatus(NewsletterModel::STATUS_SENT);
      }
    }
    return $queue->save();
  }
}