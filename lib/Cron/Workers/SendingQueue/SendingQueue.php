<?php
namespace MailPoet\Cron\Workers\SendingQueue;

use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Links;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Mailer as MailerTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Newsletter as NewsletterTask;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\SendingQueue as SendingQueueModel;
use MailPoet\Models\StatisticsNewsletters as StatisticsNewslettersModel;
use MailPoet\Models\Subscriber as SubscriberModel;
use MailPoet\Segments\SubscribersFinder;
use MailPoet\WP\Hooks as WPHooks;

if(!defined('ABSPATH')) exit;

class SendingQueue {
  public $mailer_task;
  public $newsletter_task;
  public $timer;
  const BATCH_SIZE = 20;

  function __construct($timer = false, $mailer_task = false, $newsletter_task = false) {
    $this->mailer_task = ($mailer_task) ? $mailer_task : new MailerTask();
    $this->newsletter_task = ($newsletter_task) ? $newsletter_task : new NewsletterTask();
    $this->timer = ($timer) ? $timer : microtime(true);
    $this->batch_size = WPHooks::applyFilters('mailpoet_cron_worker_sending_queue_batch_size', self::BATCH_SIZE);
  }

  function process() {
    $this->enforceSendingAndExecutionLimits();
    foreach(self::getRunningQueues() as $queue) {
      $newsletter = $this->newsletter_task->getNewsletterFromQueue($queue);
      if(!$newsletter) {
        continue;
      }
      // pre-process newsletter (render, replace shortcodes/links, etc.)
      $newsletter = $this->newsletter_task->preProcessNewsletter($newsletter, $queue);
      if(!$newsletter) {
        $queue->delete();
        continue;
      }
      // clone the original object to be used for processing
      $_newsletter = (object)$newsletter->asArray();
      // configure mailer
      $this->mailer_task->configureMailer($newsletter);
      // get newsletter segments
      $newsletter_segments_ids = $this->newsletter_task->getNewsletterSegments($newsletter);
      // get subscribers
      $queue->subscribers = $queue->getSubscribers();
      $subscriber_batches = array_chunk(
        $queue->subscribers['to_process'],
        $this->batch_size
      );
      foreach($subscriber_batches as $subscribers_to_process_ids) {
        if(!empty($newsletter_segments_ids[0])) {
          // Check that subscribers are in segments
          $finder = new SubscribersFinder();
          $found_subscribers_ids = $finder->findSubscribersInSegments($subscribers_to_process_ids, $newsletter_segments_ids);
          $found_subscribers = SubscriberModel::whereIn('id', $subscribers_to_process_ids)
            ->whereNull('deleted_at')
            ->findMany();
        } else {
          // No segments = Welcome emails
          $found_subscribers = SubscriberModel::whereIn('id', $subscribers_to_process_ids)
            ->whereNull('deleted_at')
            ->findMany();
          $found_subscribers_ids = SubscriberModel::extractSubscribersIds($found_subscribers);
        }
        // if some subscribers weren't found, remove them from the processing list
        if(count($found_subscribers_ids) !== count($subscribers_to_process_ids)) {
          $subscribers_to_remove = array_diff(
            $subscribers_to_process_ids,
            $found_subscribers_ids
          );
          $queue->removeSubscribers($subscribers_to_remove);
          if(!count($queue->subscribers['to_process'])) {
            $this->newsletter_task->markNewsletterAsSent($newsletter, $queue);
            continue;
          }
        }
        $queue = $this->processQueue(
          $queue,
          $_newsletter,
          $found_subscribers
        );
        if($queue->status === SendingQueueModel::STATUS_COMPLETED) {
          $this->newsletter_task->markNewsletterAsSent($newsletter, $queue);
        }
        $this->enforceSendingAndExecutionLimits();
      }
    }
  }

  function processQueue($queue, $newsletter, $subscribers) {
    // determine if processing is done in bulk or individually
    $processing_method = $this->mailer_task->getProcessingMethod();
    $prepared_newsletters = array();
    $prepared_subscribers = array();
    $prepared_subscribers_ids = array();
    $unsubscribe_urls = array();
    $statistics = array();
    foreach($subscribers as $subscriber) {
      // render shortcodes and replace subscriber data in tracked links
      $prepared_newsletters[] =
        $this->newsletter_task->prepareNewsletterForSending(
          $newsletter,
          $subscriber,
          $queue
        );
      // format subscriber name/address according to mailer settings
      $prepared_subscribers[] = $this->mailer_task->prepareSubscriberForSending(
        $subscriber
      );
      $prepared_subscribers_ids[] = $subscriber->id;
      // save personalized unsubsribe link
      $unsubscribe_urls[] = Links::getUnsubscribeUrl($queue, $subscriber->id);
      // keep track of values for statistics purposes
      $statistics[] = array(
        'newsletter_id' => $newsletter->id,
        'subscriber_id' => $subscriber->id,
        'queue_id' => $queue->id
      );
      if($processing_method === 'individual') {
        $queue = $this->sendNewsletters(
          $queue,
          $prepared_subscribers_ids,
          $prepared_newsletters[0],
          $prepared_subscribers[0],
          $statistics,
          array('unsubscribe_url' => $unsubscribe_urls[0])
        );
        $prepared_newsletters = array();
        $prepared_subscribers = array();
        $prepared_subscribers_ids = array();
        $unsubscribe_urls = array();
        $statistics = array();
      }
    }
    if($processing_method === 'bulk') {
      $queue = $this->sendNewsletters(
        $queue,
        $prepared_subscribers_ids,
        $prepared_newsletters,
        $prepared_subscribers,
        $statistics,
        array('unsubscribe_url' => $unsubscribe_urls)
      );
    }
    return $queue;
  }

  function sendNewsletters(
    $queue, $prepared_subscribers_ids, $prepared_newsletters,
    $prepared_subscribers, $statistics, $extra_params = array()
  ) {
    // send newsletter
    $send_result = $this->mailer_task->send(
      $prepared_newsletters,
      $prepared_subscribers,
      $extra_params
    );
    // log error message and schedule retry/pause sending
    if($send_result['response'] === false) {
      MailerLog::processError(
        $send_result['operation'],
        $send_result['error_message']
      );
    }
    // update processed/to process list
    if(!$queue->updateProcessedSubscribers($prepared_subscribers_ids)) {
      MailerLog::processError(
        'processed_list_update',
        sprintf('QUEUE-%d-PROCESSED-LIST-UPDATE', $queue->id),
        null,
        true
      );
    }
    // log statistics
    StatisticsNewslettersModel::createMultiple($statistics);
    // update the sent count
    $this->mailer_task->updateSentCount();
    // enforce execution limits if queue is still being processed
    if($queue->status !== SendingQueueModel::STATUS_COMPLETED) {
      $this->enforceSendingAndExecutionLimits();
    }
    return $queue;
  }

  function enforceSendingAndExecutionLimits() {
    // abort if execution limit is reached
    CronHelper::enforceExecutionLimit($this->timer);
    // abort if sending limit has been reached
    MailerLog::enforceExecutionRequirements();
  }

  static function getRunningQueues() {
    return SendingQueueModel::orderByAsc('priority')
      ->orderByAsc('created_at')
      ->whereNull('deleted_at')
      ->whereNull('status')
      ->whereNull('type')
      ->findMany();
  }
}