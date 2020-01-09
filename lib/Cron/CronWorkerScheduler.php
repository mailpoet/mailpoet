<?php

namespace MailPoet\Cron;

use MailPoet\Models\ScheduledTask;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class CronWorkerScheduler {
  /** @var WPFunctions */
  private $wp;

  public function __construct(WPFunctions $wp) {
    $this->wp = $wp;
  }

  public function schedule($taskType, $nextRunDate) {
    $alreadyScheduled = ScheduledTask::where('type', $taskType)
      ->whereNull('deleted_at')
      ->where('status', ScheduledTask::STATUS_SCHEDULED)
      ->findMany();
    if ($alreadyScheduled) {
      return false;
    }
    $task = ScheduledTask::create();
    $task->type = $taskType;
    $task->status = ScheduledTask::STATUS_SCHEDULED;
    $task->priority = ScheduledTask::PRIORITY_LOW;
    $task->scheduledAt = $nextRunDate;
    $task->save();
    return $task;
  }

  public function reschedule(ScheduledTask $task, $timeout) {
    $scheduledAt = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
    $task->scheduledAt = $scheduledAt->addMinutes($timeout);
    $task->setExpr('updated_at', 'NOW()');
    $task->status = ScheduledTask::STATUS_SCHEDULED;
    $task->save();
  }
}
