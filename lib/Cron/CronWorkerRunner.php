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
  private $cron_helper;

  /** @var CronWorkerScheduler */
  private $cron_worker_scheduler;

  /** @var WPFunctions */
  private $wp;

  public function __construct(CronHelper $cron_helper, CronWorkerScheduler $cron_worker_scheduler, WPFunctions $wp) {
    $this->timer = microtime(true);
    $this->cron_helper = $cron_helper;
    $this->cron_worker_scheduler = $cron_worker_scheduler;
    $this->wp = $wp;
  }

  public function run(CronWorkerInterface $worker) {
    // abort if execution limit is reached
    $this->cron_helper->enforceExecutionLimit($this->timer);
    $due_tasks = $this->getDueTasks($worker);
    $running_tasks = $this->getRunningTasks($worker);

    if (!$worker->checkProcessingRequirements()) {
      foreach (array_merge($due_tasks, $running_tasks) as $task) {
        $task->delete();
      }
      return false;
    }

    $worker->init();

    if (!$due_tasks && !$running_tasks) {
      if ($worker->scheduleAutomatically()) {
        $this->cron_worker_scheduler->schedule($worker->getTaskType(), $worker->getNextRunDate());
      }
      return false;
    }

    $task = null;
    try {
      foreach ($due_tasks as $i => $task) {
        $this->prepareTask($worker, $task);
      }
      foreach ($running_tasks as $i => $task) {
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
    $this->cron_helper->enforceExecutionLimit($this->timer);

    $prepare_completed = $worker->prepareTaskStrategy($task, $this->timer);
    if ($prepare_completed) {
      $task->status = null;
      $task->save();
    }
  }

  private function processTask(CronWorkerInterface $worker, ScheduledTask $task) {
    // abort if execution limit is reached
    $this->cron_helper->enforceExecutionLimit($this->timer);

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
    $current_time = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
    $updated_at = Carbon::createFromTimestamp(strtotime((string)$task->updated_at));

    // If the task is running for too long consider it stuck and reschedule
    if (!empty($task->updated_at) && $updated_at->diffInMinutes($current_time, false) > self::TASK_RUN_TIMEOUT) {
      $this->stopProgress($task);
      $this->cron_worker_scheduler->reschedule($task, self::TIMED_OUT_TASK_RESCHEDULE_TIMEOUT);
      return true;
    }
    return false;
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

  private function complete(ScheduledTask $task) {
    $task->processed_at = $this->wp->currentTime('mysql');
    $task->status = ScheduledTask::STATUS_COMPLETED;
    $task->save();
  }
}
