<?php

namespace MailPoet\Cron;

use Carbon\Carbon;
use MailPoet\Models\ScheduledTask;
use MailPoet\WP\Functions as WPFunctions;

class CronWorkerScheduler {
  /** @var WPFunctions */
  private $wp;

  function __construct(WPFunctions $wp) {
    $this->wp = $wp;
  }

  function schedule($task_type, $next_run_date) {
    $already_scheduled = ScheduledTask::where('type', $task_type)
      ->whereNull('deleted_at')
      ->where('status', ScheduledTask::STATUS_SCHEDULED)
      ->findMany();
    if ($already_scheduled) {
      return false;
    }
    $task = ScheduledTask::create();
    $task->type = $task_type;
    $task->status = ScheduledTask::STATUS_SCHEDULED;
    $task->priority = ScheduledTask::PRIORITY_LOW;
    $task->scheduled_at = $next_run_date;
    $task->save();
    return $task;
  }

  function reschedule(ScheduledTask $task, $timeout) {
    $scheduled_at = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
    $task->scheduled_at = $scheduled_at->addMinutes($timeout);
    $task->setExpr('updated_at', 'NOW()');
    $task->status = ScheduledTask::STATUS_SCHEDULED;
    $task->save();
  }
}
