<?php
namespace MailPoet\Cron\Workers;

use Carbon\Carbon;
use MailPoet\Cron\CronHelper;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Setting;
use MailPoet\Services\Bridge;

if(!defined('ABSPATH')) exit;

class PremiumKeyCheck {
  const TASK_TYPE = 'premium_key_check';
  const UNAVAILABLE_SERVICE_RESCHEDULE_TIMEOUT = 60;

  public $timer;
  public $bridge;

  function __construct($timer = false) {
    $this->timer = ($timer) ? $timer : microtime(true);
    // abort if execution limit is reached
    CronHelper::enforceExecutionLimit($this->timer);
  }

  function initApi() {
    if(!$this->bridge) {
      $this->bridge = new Bridge();
    }
  }

  function process() {
    if(!Bridge::isPremiumKeySpecified()) {
      return false;
    }

    $this->initApi();

    $scheduled_queues = self::getScheduledQueues();
    $running_queues = self::getRunningQueues();

    if(!$scheduled_queues && !$running_queues) {
      self::schedule();
      return false;
    }

    foreach($scheduled_queues as $i => $queue) {
      $this->prepareQueue($queue);
    }
    foreach($running_queues as $i => $queue) {
      $this->processQueue($queue);
    }

    return true;
  }

  static function schedule() {
    $already_scheduled = SendingQueue::where('type', self::TASK_TYPE)
      ->whereNull('deleted_at')
      ->where('status', SendingQueue::STATUS_SCHEDULED)
      ->findMany();
    if($already_scheduled) {
      return false;
    }
    $queue = SendingQueue::create();
    $queue->type = self::TASK_TYPE;
    $queue->status = SendingQueue::STATUS_SCHEDULED;
    $queue->priority = SendingQueue::PRIORITY_LOW;
    $queue->scheduled_at = self::getNextRunDate();
    $queue->newsletter_id = 0;
    $queue->save();
    return $queue;
  }

  function prepareQueue(SendingQueue $queue) {
    $queue->status = null;
    $queue->save();

    // abort if execution limit is reached
    CronHelper::enforceExecutionLimit($this->timer);

    return true;
  }

  function processQueue(SendingQueue $queue) {
    // abort if execution limit is reached
    CronHelper::enforceExecutionLimit($this->timer);

    try {
      $premium_key = Setting::getValue(Bridge::PREMIUM_KEY_STATE_SETTING_NAME);
      $result = $this->bridge->checkPremiumKey($premium_key);
    } catch (\Exception $e) {
      $result = false;
    }

    if(empty($result['code']) || $result['code'] == Bridge::CHECK_ERROR_UNAVAILABLE) {
      // reschedule the check
      $scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
      $queue->scheduled_at = $scheduled_at->addMinutes(
        self::UNAVAILABLE_SERVICE_RESCHEDULE_TIMEOUT
      );
      $queue->save();
      return false;
    }

    $queue->processed_at = current_time('mysql');
    $queue->status = SendingQueue::STATUS_COMPLETED;
    $queue->save();

    return true;
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
    return SendingQueue::where('type', self::TASK_TYPE)
      ->$dateWhere('scheduled_at', Carbon::createFromTimestamp(current_time('timestamp')))
      ->whereNull('deleted_at')
      ->where('status', SendingQueue::STATUS_SCHEDULED)
      ->findMany();
  }

  static function getRunningQueues() {
    return SendingQueue::where('type', self::TASK_TYPE)
      ->whereLte('scheduled_at', Carbon::createFromTimestamp(current_time('timestamp')))
      ->whereNull('deleted_at')
      ->whereNull('status')
      ->findMany();
  }

  static function getAllDueQueues() {
    $scheduled_queues = self::getScheduledQueues();
    $running_queues = self::getRunningQueues();
    return array_merge((array)$scheduled_queues, (array)$running_queues);
  }

  static function getFutureQueues() {
    return self::getScheduledQueues(true);
  }
}
