<?php
namespace MailPoet\Cron\Workers;

use Carbon\Carbon;
use MailPoet\Cron\CronHelper;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class Bounce {
  const BOUNCED_HARD = 'hard';
  const BOUNCED_SOFT = 'soft';
  const NOT_BOUNCED = null;
  const BATCH_SIZE = 100;

  public $timer;
  public $api;

  function __construct($timer = false) {
    $this->timer = ($timer) ? $timer : microtime(true);
    // abort if execution limit is reached
    CronHelper::enforceExecutionLimit($this->timer);
  }

  static function checkBounceSyncAvailable() {
    $mailer_config = Mailer::getMailerConfig();
    return !empty($mailer_config['method'])
      && $mailer_config['method'] === Mailer::METHOD_MAILPOET;
  }

  function initApi() {
    if(!$this->api) {
      $mailer_config = Mailer::getMailerConfig();
      $this->api = new Bounce\API($mailer_config['mailpoet_api_key']);
    }
  }

  function process() {
    if(!self::checkBounceSyncAvailable()) {
      return false;
    }

    $this->initApi();

    $scheduled_queues = self::getScheduledQueues();
    $running_queues = self::getRunningQueues();

    if(!$scheduled_queues && !$running_queues) {
      self::scheduleBounceSync();
      return false;
    }

    foreach($scheduled_queues as $i => $queue) {
      $this->prepareBounceQueue($queue);
    }
    foreach($running_queues as $i => $queue) {
      $this->processBounceQueue($queue);
    }

    return true;
  }

  static function scheduleBounceSync() {
    $already_scheduled = SendingQueue::where('type', 'bounce')
      ->whereNull('deleted_at')
      ->where('status', SendingQueue::STATUS_SCHEDULED)
      ->findMany();
    if($already_scheduled) {
      return false;
    }
    $queue = SendingQueue::create();
    $queue->type = 'bounce';
    $queue->status = SendingQueue::STATUS_SCHEDULED;
    $queue->priority = SendingQueue::PRIORITY_LOW;
    $queue->scheduled_at = self::getNextRunDate();
    $queue->save();
    return $queue;
  }

  function prepareBounceQueue(SendingQueue $queue) {
    $subscribers = Subscriber::select('id')
      ->whereNull('deleted_at')
      ->whereIn('status', array(
        Subscriber::STATUS_SUBSCRIBED,
        Subscriber::STATUS_UNCONFIRMED
      ))
      ->findArray();
    $subscribers = Helpers::arrayColumn($subscribers, 'id');

    if (empty($subscribers)) {
      $queue->delete();
      return false;
    }

    // update current queue
    $queue->subscribers = serialize(
      array(
        'to_process' => $subscribers
      )
    );
    $queue->count_total = $queue->count_to_process = count($subscribers);
    $queue->status = null;
    $queue->save();

    // abort if execution limit is reached
    CronHelper::enforceExecutionLimit($this->timer);

    return true;
  }

  function processBounceQueue(SendingQueue $queue) {
    $queue->subscribers = $queue->getSubscribers();
    if(empty($queue->subscribers['to_process'])) {
      $queue->delete();
      return false;
    }

    $subscriber_batches = array_chunk(
      $queue->subscribers['to_process'],
      self::BATCH_SIZE
    );

    foreach ($subscriber_batches as $subscribers_to_process_ids) {
      // abort if execution limit is reached
      CronHelper::enforceExecutionLimit($this->timer);

      $subscriber_emails = Subscriber::select('email')
        ->whereIn('id', $subscribers_to_process_ids)
        ->whereNull('deleted_at')
        ->findArray();
      $subscriber_emails = Helpers::arrayColumn($subscriber_emails, 'email');

      $this->processEmails($subscriber_emails);

      $queue->updateProcessedSubscribers($subscribers_to_process_ids);
    }

    return true;
  }

  function processEmails(array $subscriber_emails) {
    $checked_emails = $this->api->check($subscriber_emails);
    $this->processApiResponse((array) $checked_emails);
  }

  function processApiResponse(array $checked_emails) {
    foreach ($checked_emails as $email) {
      if(!isset($email['address'], $email['bounce'])) {
        continue;
      }
      if($email['bounce'] === self::BOUNCED_HARD) {
        $subscriber = Subscriber::findOne($email['address']);
        $subscriber->status = Subscriber::STATUS_BOUNCED;
        $subscriber->save();
      }
    }
  }

  static function getNextRunDate() {
    $date = Carbon::createFromTimestamp(current_time('timestamp'));
    // Random day of the next week
    $date->setISODate($date->format('o'), $date->format('W') + 1, mt_rand(1, 7));
    $date->startOfDay();
    return $date;
  }

  static function getScheduledQueues($future = false) {
    $dateWhere = ($future) ? 'whereGt' : 'whereLte';
    return SendingQueue::where('type', 'bounce')
      ->$dateWhere('scheduled_at', Carbon::createFromTimestamp(current_time('timestamp')))
      ->whereNull('deleted_at')
      ->where('status', SendingQueue::STATUS_SCHEDULED)
      ->findMany();
  }

  static function getRunningQueues() {
    return SendingQueue::where('type', 'bounce')
      ->whereLte('scheduled_at', Carbon::createFromTimestamp(current_time('timestamp')))
      ->whereNull('deleted_at')
      ->whereNull('status')
      ->findMany();
  }

  static function getAllDueQueues() {
    $scheduled_queues = self::getScheduledQueues();
    $running_queues = self::getRunningQueues();
    return array_merge((array) $scheduled_queues, (array) $running_queues);
  }

  static function getFutureQueues() {
    return self::getScheduledQueues(true);
  }
}
