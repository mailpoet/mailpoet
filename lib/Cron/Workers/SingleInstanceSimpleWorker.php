<?php

namespace MailPoet\Cron\Workers;

use Carbon\Carbon;
use MailPoet\Cron\CronHelper;
use MailPoet\Models\ScheduledTask;
use MailPoet\WP\Functions as WPFunctions;

abstract class SingleInstanceSimpleWorker extends SimpleWorker {
  const TASK_RUN_TIMEOUT = 120;
  const TIMED_OUT_TASK_RESCHEDULE_TIMEOUT = 5;

  function processTask(ScheduledTask $task) {
    // abort if execution limit is reached
    CronHelper::enforceExecutionLimit($this->timer);

    if ($this->isInProgress($task)) {
      return false;
    }
    if ($this->rescheduleOutdated($task)) {
      return false;
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

  private function isInProgress(ScheduledTask $task) {
    $meta = $task->getMeta();
    if (!empty($meta['in_progress'])) {
      // Do not run multiple instances of the task
      return true;
    }
    return false;
  }

  function startProgress(ScheduledTask $task) {
    $task->meta = array_merge($task->getMeta(), ['in_progress' => true]);
    $task->save();
  }

  function stopProgress(ScheduledTask $task) {
    $task->meta = array_merge($task->getMeta(), ['in_progress' => null]);
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
}
