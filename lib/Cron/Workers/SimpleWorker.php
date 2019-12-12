<?php

namespace MailPoet\Cron\Workers;

use MailPoet\Cron\CronHelper;
use MailPoet\Cron\CronWorkerScheduler;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\ScheduledTask;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

abstract class SimpleWorker {
  private $wp;
  const TASK_TYPE = null;
  const TASK_BATCH_SIZE = 5;
  const AUTOMATIC_SCHEDULING = true;

  const SUPPORT_MULTIPLE_INSTANCES = true;
  const TASK_RUN_TIMEOUT = 120;
  const TIMED_OUT_TASK_RESCHEDULE_TIMEOUT = 5;

  /** @var CronHelper */
  protected $cron_helper;

  /** @var CronWorkerScheduler */
  protected $cron_worker_scheduler;

  function __construct() {
    if (static::TASK_TYPE === null) {
      throw new \Exception('Constant TASK_TYPE is not defined on subclass ' . get_class($this));
    }

    $this->wp = new WPFunctions();
    $this->cron_helper = ContainerWrapper::getInstance()->get(CronHelper::class);
    $this->cron_worker_scheduler = ContainerWrapper::getInstance()->get(CronWorkerScheduler::class);
  }

  function checkProcessingRequirements() {
    return true;
  }

  function init() {
  }

  function process($timer = false) {
    $timer = $timer ?: microtime(true);

    // abort if execution limit is reached
    $this->cron_helper->enforceExecutionLimit($timer);
    $scheduled_tasks = $this->getDueTasks();
    $running_tasks = $this->getRunningTasks();

    if (!$this->checkProcessingRequirements()) {
      foreach (array_merge($scheduled_tasks, $running_tasks) as $task) {
        $task->delete();
      }
      return false;
    }

    $this->init();


    if (!$scheduled_tasks && !$running_tasks) {
      if (static::AUTOMATIC_SCHEDULING) {
        $this->schedule();
      }
      return false;
    }

    $task = null;
    try {
      foreach ($scheduled_tasks as $i => $task) {
        $this->prepareTask($task, $timer);
      }
      foreach ($running_tasks as $i => $task) {
        $this->processTask($task, $timer);
      }
    } catch (\Exception $e) {
      if ($task && $e->getCode() !== CronHelper::DAEMON_EXECUTION_LIMIT_REACHED) {
        $task->rescheduleProgressively();
      }
      throw $e;
    }

    return true;
  }

  function schedule() {
    $this->cron_worker_scheduler->schedule(static::TASK_TYPE, static::getNextRunDate());
  }

  function prepareTask(ScheduledTask $task, $timer) {
    // abort if execution limit is reached
    $this->cron_helper->enforceExecutionLimit($timer);

    $prepare_completed = $this->prepareTaskStrategy($task, $timer);
    if ($prepare_completed) {
      $task->status = null;
      $task->save();
    }
  }

  function processTask(ScheduledTask $task, $timer) {
    // abort if execution limit is reached
    $this->cron_helper->enforceExecutionLimit($timer);

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
      $completed = $this->processTaskStrategy($task, $timer);
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

  function prepareTaskStrategy(ScheduledTask $task, $timer) {
    return true;
  }

  function processTaskStrategy(ScheduledTask $task, $timer) {
    return true;
  }

  function complete(ScheduledTask $task) {
    $task->processed_at = $this->wp->currentTime('mysql');
    $task->status = ScheduledTask::STATUS_COMPLETED;
    $task->save();
  }

  function reschedule(ScheduledTask $task, $timeout) {
    $this->cron_worker_scheduler->reschedule($task, $timeout);
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

  function getNextRunDate() {
    $wp = new WPFunctions();
    $date = Carbon::createFromTimestamp($wp->currentTime('timestamp'));
    // Random day of the next week
    $date->setISODate((int)$date->format('o'), ((int)$date->format('W')) + 1, mt_rand(1, 7));
    $date->startOfDay();
    return $date;
  }

  function getDueTasks() {
    return ScheduledTask::findDueByType(static::TASK_TYPE, self::TASK_BATCH_SIZE);
  }

  function getRunningTasks() {
    return ScheduledTask::findRunningByType(static::TASK_TYPE, self::TASK_BATCH_SIZE);
  }

  function getCompletedTasks() {
    return ScheduledTask::findCompletedByType(static::TASK_TYPE, self::TASK_BATCH_SIZE);
  }
}
