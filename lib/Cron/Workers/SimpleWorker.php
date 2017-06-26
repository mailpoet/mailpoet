<?php
namespace MailPoet\Cron\Workers;

use Carbon\Carbon;
use MailPoet\Cron\CronHelper;
use MailPoet\Models\ScheduledTask;

if(!defined('ABSPATH')) exit;

abstract class SimpleWorker {
  public $timer;

  function __construct($timer = false) {
    if(!defined('static::TASK_TYPE')) {
      throw new \Exception('Constant TASK_TYPE is not defined on subclass ' . get_class($this));
    }
    $this->timer = ($timer) ? $timer : microtime(true);
    // abort if execution limit is reached
    CronHelper::enforceExecutionLimit($this->timer);
  }

  function checkProcessingRequirements() {
    return true;
  }

  function process() {
    if(!$this->checkProcessingRequirements()) {
      return false;
    }

    if(is_callable(array($this, 'init'))) {
      $this->init();
    }

    $scheduled_tasks = self::getScheduledTasks();
    $running_tasks = self::getRunningTasks();

    if(!$scheduled_tasks && !$running_tasks) {
      self::schedule();
      return false;
    }

    foreach($scheduled_tasks as $i => $task) {
      $this->prepareTask($task);
    }
    foreach($running_tasks as $i => $task) {
      $this->processTask($task);
    }

    return true;
  }

  static function schedule() {
    $already_scheduled = ScheduledTask::where('type', static::TASK_TYPE)
      ->whereNull('deleted_at')
      ->where('status', ScheduledTask::STATUS_SCHEDULED)
      ->findMany();
    if($already_scheduled) {
      return false;
    }
    $task = ScheduledTask::create();
    $task->type = static::TASK_TYPE;
    $task->status = ScheduledTask::STATUS_SCHEDULED;
    $task->priority = ScheduledTask::PRIORITY_LOW;
    $task->scheduled_at = self::getNextRunDate();
    $task->save();
    return $task;
  }

  function prepareTask(ScheduledTask $task) {
    $task->status = null;
    $task->save();

    // abort if execution limit is reached
    CronHelper::enforceExecutionLimit($this->timer);

    return true;
  }

  function processTask(ScheduledTask $task) {
    // abort if execution limit is reached
    CronHelper::enforceExecutionLimit($this->timer);

    if($this->processTaskStrategy($task)) {
      $this->complete($task);
      return true;
    }

    return false;
  }

  function processTaskStrategy(ScheduledTask $task) {
    return true;
  }

  function complete(ScheduledTask $task) {
    $task->processed_at = current_time('mysql');
    $task->status = ScheduledTask::STATUS_COMPLETED;
    $task->save();
  }

  function reschedule(ScheduledTask $task, $timeout) {
    $scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $task->scheduled_at = $scheduled_at->addMinutes($timeout);
    $task->save();
  }

  static function getNextRunDate() {
    $date = Carbon::createFromTimestamp(current_time('timestamp'));
    // Random day of the next week
    $date->setISODate($date->format('o'), $date->format('W') + 1, mt_rand(1, 7));
    $date->startOfDay();
    return $date;
  }

  static function getScheduledTasks($future = false) {
    $dateWhere = ($future) ? 'whereGt' : 'whereLte';
    return ScheduledTask::where('type', static::TASK_TYPE)
      ->$dateWhere('scheduled_at', Carbon::createFromTimestamp(current_time('timestamp')))
      ->whereNull('deleted_at')
      ->where('status', ScheduledTask::STATUS_SCHEDULED)
      ->findMany();
  }

  static function getRunningTasks() {
    return ScheduledTask::where('type', static::TASK_TYPE)
      ->whereLte('scheduled_at', Carbon::createFromTimestamp(current_time('timestamp')))
      ->whereNull('deleted_at')
      ->whereNull('status')
      ->findMany();
  }

  static function getAllDueTasks() {
    $scheduled_tasks = self::getScheduledTasks();
    $running_tasks = self::getRunningTasks();
    return array_merge((array)$scheduled_tasks, (array)$running_tasks);
  }

  static function getFutureTasks() {
    return self::getScheduledTasks(true);
  }
}
