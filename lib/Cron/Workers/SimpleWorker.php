<?php

namespace MailPoet\Cron\Workers;

use Carbon\Carbon;
use MailPoet\Cron\CronHelper;
use MailPoet\Models\ScheduledTask;
use MailPoet\WP\Functions as WPFunctions;

abstract class SimpleWorker {
  public $timer;
  private $wp;
  const TASK_TYPE = null;
  const TASK_BATCH_SIZE = 5;
  const AUTOMATIC_SCHEDULING = true;

  const SUPPORT_MULTIPLE_INSTANCES = true;
  const TASK_RUN_TIMEOUT = 120;
  const TIMED_OUT_TASK_RESCHEDULE_TIMEOUT = 5;

  function __construct($timer = false) {
    if (static::TASK_TYPE === null) {
      throw new \Exception('Constant TASK_TYPE is not defined on subclass ' . get_class($this));
    }
    $this->timer = ($timer) ? $timer : microtime(true);
    // abort if execution limit is reached
    CronHelper::enforceExecutionLimit($this->timer);
    $this->wp = new WPFunctions();
  }

  function checkProcessingRequirements() {
    return true;
  }

  function init() {
  }

  function process() {
    if (!$this->checkProcessingRequirements()) {
      return false;
    }

    $this->init();

    $scheduled_tasks = self::getScheduledTasks();
    $running_tasks = self::getRunningTasks();

    if (!$scheduled_tasks && !$running_tasks) {
      if (static::AUTOMATIC_SCHEDULING) {
        self::schedule();
      }
      return false;
    }

    $task = null;
    try {
      foreach ($scheduled_tasks as $i => $task) {
        $this->prepareTask($task);
      }
      foreach ($running_tasks as $i => $task) {
        $this->processTask($task);
      }
    } catch (\Exception $e) {
      if ($task) {
        $task->rescheduleProgressively();
      }
      throw $e;
    }

    return true;
  }

  static function schedule() {
    $already_scheduled = ScheduledTask::where('type', static::TASK_TYPE)
      ->whereNull('deleted_at')
      ->where('status', ScheduledTask::STATUS_SCHEDULED)
      ->findMany();
    if ($already_scheduled) {
      return false;
    }
    $task = ScheduledTask::create();
    $task->type = static::TASK_TYPE;
    $task->status = ScheduledTask::STATUS_SCHEDULED;
    $task->priority = ScheduledTask::PRIORITY_LOW;
    $task->scheduled_at = static::getNextRunDate();
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

    if (!static::SUPPORT_MULTIPLE_INSTANCES) {
      if ($this->rescheduleOutdated($task)) {
        return false;
      }
      if ($this->isInProgress($task)) {
        return false;
      }
    }

    $this->startProgress($task);

    try {
      $completed = $this->processTaskStrategy($task);
    } catch (\Exception $e) {
      $this->stopProgress($task);
      throw $e;
    }

    if ($completed) {
      $this->complete($task);
    }

    $this->stopProgress($task);

    return (bool)$completed;
  }

  function processTaskStrategy(ScheduledTask $task) {
    return true;
  }

  function complete(ScheduledTask $task) {
    $task->processed_at = $this->wp->currentTime('mysql');
    $task->status = ScheduledTask::STATUS_COMPLETED;
    $task->save();
  }

  function reschedule(ScheduledTask $task, $timeout) {
    $scheduled_at = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
    $task->scheduled_at = $scheduled_at->addMinutes($timeout);
    $task->setExpr('updated_at', 'NOW()');
    $task->status = ScheduledTask::STATUS_SCHEDULED;
    $task->save();
  }

  private function isInProgress(ScheduledTask $task) {
    if (!empty($task->in_progress)) {
      // Do not run multiple instances of the task
      return true;
    }
    return false;
  }

  private function startProgress(ScheduledTask $task) {
    $task->in_progress = true;
    $task->save();
  }

  private function stopProgress(ScheduledTask $task) {
    $task->in_progress = false;
    $task->save();
  }

  private function rescheduleOutdated(ScheduledTask $task) {
    $current_time = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $updated_at = Carbon::createFromTimestamp(strtotime($task->updated_at));

    // If the task is running for too long consider it stuck and reschedule
    if (!empty($task->updated_at) && $updated_at->diffInMinutes($current_time, false) > self::TASK_RUN_TIMEOUT) {
      $this->stopProgress($task);
      $this->reschedule($task, self::TIMED_OUT_TASK_RESCHEDULE_TIMEOUT);
      return true;
    }

    return false;
  }

  static function getNextRunDate() {
    $wp = new WPFunctions();
    $date = Carbon::createFromTimestamp($wp->currentTime('timestamp'));
    // Random day of the next week
    $date->setISODate((int)$date->format('o'), ((int)$date->format('W')) + 1, mt_rand(1, 7));
    $date->startOfDay();
    return $date;
  }

  /**
   * @param bool $future
   * @return ScheduledTask[]
   */
  static function getScheduledTasks($future = false) {
    $dateWhere = ($future) ? 'whereGt' : 'whereLte';
    $wp = new WPFunctions();
    return ScheduledTask::where('type', static::TASK_TYPE)
      ->$dateWhere('scheduled_at', Carbon::createFromTimestamp($wp->currentTime('timestamp')))
      ->whereNull('deleted_at')
      ->where('status', ScheduledTask::STATUS_SCHEDULED)
      ->limit(self::TASK_BATCH_SIZE)
      ->findMany();
  }

  static function getRunningTasks() {
    $wp = new WPFunctions();
    return ScheduledTask::where('type', static::TASK_TYPE)
      ->whereLte('scheduled_at', Carbon::createFromTimestamp($wp->currentTime('timestamp')))
      ->whereNull('deleted_at')
      ->whereNull('status')
      ->limit(self::TASK_BATCH_SIZE)
      ->findMany();
  }

  static function getDueTasks() {
    $scheduled_tasks = self::getScheduledTasks();
    $running_tasks = self::getRunningTasks();
    return array_merge((array)$scheduled_tasks, (array)$running_tasks);
  }

  static function getCompletedTasks() {
    return ScheduledTask::where('type', static::TASK_TYPE)
      ->whereNull('deleted_at')
      ->where('status', ScheduledTask::STATUS_COMPLETED)
      ->limit(self::TASK_BATCH_SIZE)
      ->findMany();
  }
}
