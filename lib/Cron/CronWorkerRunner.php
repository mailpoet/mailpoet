<?php

namespace MailPoet\Cron;

use MailPoet\Models\ScheduledTask;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class CronWorkerRunner {
  const TASK_BATCH_SIZE = 5;
  const TASK_RUN_TIMEOUT = 120;
  const TIMED_OUT_TASK_RESCHEDULE_TIMEOUT = 5;

  /** @var float */
  private $timer;

  /** @var CronHelper */
  private $cronHelper;

  /** @var CronWorkerScheduler */
  private $cronWorkerScheduler;

  /** @var WPFunctions */
  private $wp;

  public function __construct(CronHelper $cronHelper, CronWorkerScheduler $cronWorkerScheduler, WPFunctions $wp) {
    $this->timer = microtime(true);
    $this->cronHelper = $cronHelper;
    $this->cronWorkerScheduler = $cronWorkerScheduler;
    $this->wp = $wp;
  }

  public function run(CronWorkerInterface $worker) {
    // abort if execution limit is reached
    $this->cronHelper->enforceExecutionLimit($this->timer);
    $dueTasks = $this->getDueTasks($worker);
    $runningTasks = $this->getRunningTasks($worker);

    if (!$worker->checkProcessingRequirements()) {
      foreach (array_merge($dueTasks, $runningTasks) as $task) {
        $task->delete();
      }
      return false;
    }

    $worker->init();

    if (!$dueTasks && !$runningTasks) {
      if ($worker->scheduleAutomatically()) {
        $this->cronWorkerScheduler->schedule($worker->getTaskType(), $worker->getNextRunDate());
      }
      return false;
    }

    $task = null;
    try {
      foreach ($dueTasks as $i => $task) {
        $this->prepareTask($worker, $task);
      }
      foreach ($runningTasks as $i => $task) {
        $this->processTask($worker, $task);
      }
    } catch (\Exception $e) {
      if ($task && $e->getCode() !== CronHelper::DAEMON_EXECUTION_LIMIT_REACHED) {
        $task->rescheduleProgressively();
      }
      throw $e;
    }

    return true;
  }

  private function getDueTasks(CronWorkerInterface $worker) {
    return ScheduledTask::findDueByType($worker->getTaskType(), self::TASK_BATCH_SIZE);
  }

  private function getRunningTasks(CronWorkerInterface $worker) {
    return ScheduledTask::findRunningByType($worker->getTaskType(), self::TASK_BATCH_SIZE);
  }

  private function prepareTask(CronWorkerInterface $worker, ScheduledTask $task) {
    // abort if execution limit is reached
    $this->cronHelper->enforceExecutionLimit($this->timer);

    $prepareCompleted = $worker->prepareTaskStrategy($task, $this->timer);
    if ($prepareCompleted) {
      $task->status = null;
      $task->save();
    }
  }

  private function processTask(CronWorkerInterface $worker, ScheduledTask $task) {
    // abort if execution limit is reached
    $this->cronHelper->enforceExecutionLimit($this->timer);

    if (!$worker->supportsMultipleInstances()) {
      if ($this->rescheduleOutdated($task)) {
        return false;
      }
      if ($this->isInProgress($task)) {
        return false;
      }
    }

    $this->startProgress($task);

    try {
      $completed = $worker->processTaskStrategy($task, $this->timer);
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

  private function rescheduleOutdated(ScheduledTask $task) {
    $currentTime = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
    $updated = strtotime((string)$task->updatedAt);
    if ($updated === false) {
      // missing updatedAt, consider this task outdated (set year to 2000) and reschedule
      $updatedAt = Carbon::createFromDate(2000);
    } else {
      $updatedAt = Carbon::createFromTimestamp($updated);
    }

    // If the task is running for too long consider it stuck and reschedule
    if (!empty($task->updatedAt) && $updatedAt->diffInMinutes($currentTime, false) > self::TASK_RUN_TIMEOUT) {
      $this->stopProgress($task);
      $this->cronWorkerScheduler->reschedule($task, self::TIMED_OUT_TASK_RESCHEDULE_TIMEOUT);
      return true;
    }
    return false;
  }

  private function isInProgress(ScheduledTask $task) {
    if (!empty($task->inProgress)) {
      // Do not run multiple instances of the task
      return true;
    }
    return false;
  }

  private function startProgress(ScheduledTask $task) {
    $task->inProgress = true;
    $task->save();
  }

  private function stopProgress(ScheduledTask $task) {
    $task->inProgress = false;
    $task->save();
  }

  private function complete(ScheduledTask $task) {
    $task->processedAt = $this->wp->currentTime('mysql');
    $task->status = ScheduledTask::STATUS_COMPLETED;
    $task->save();
  }
}
