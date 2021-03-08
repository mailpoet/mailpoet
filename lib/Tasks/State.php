<?php

namespace MailPoet\Tasks;

use MailPoet\Cron\Workers\Scheduler;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoetVendor\Carbon\Carbon;

class State {
  /**
   * @return array
   */
  public function getCountsPerStatus() {
    $stats = [
      ScheduledTask::STATUS_COMPLETED => 0,
      ScheduledTask::STATUS_PAUSED => 0,
      ScheduledTask::STATUS_SCHEDULED => 0,
      ScheduledTask::VIRTUAL_STATUS_RUNNING => 0,
    ];
    $counts = ScheduledTask::rawQuery(
      "SELECT COUNT(*) as value, status
       FROM `" . ScheduledTask::$_table . "`
       WHERE deleted_at IS NULL AND `type` = 'sending'
       GROUP BY status;"
    )->findMany();
    foreach ($counts as $count) {
      if ($count->status === null) {
        $stats[ScheduledTask::VIRTUAL_STATUS_RUNNING] = (int)$count->value;
        continue;
      }
      $stats[$count->status] = (int)$count->value;
    }
    return $stats;
  }

  /**
   * @return array
   */
  public function getLatestTasks(
    $type = null,
    $statuses = [
      ScheduledTask::STATUS_COMPLETED,
      ScheduledTask::STATUS_SCHEDULED,
      ScheduledTask::VIRTUAL_STATUS_RUNNING,
    ],
    $limit = Scheduler::TASK_BATCH_SIZE) {
    $tasks = [];
    foreach ($statuses as $status) {
      $query = ScheduledTask::orderByDesc('created_at')
        ->orderByAsc('id') // consistent order for tasks with equal timestamps
        ->whereNull('deleted_at')
        ->limit($limit);
      if ($type) {
        $query = $query->where('type', $type);
      }
      if ($status === ScheduledTask::VIRTUAL_STATUS_RUNNING) {
        $query = $query->whereNull('status');
      } else {
        $query = $query->where('status', $status);
      }
      $tasks = array_merge($tasks, $query->findMany());
    }

    return array_map(function ($task) {
      return $this->buildTaskData($task);
    }, $tasks);
  }

  /**
   * @return array
   */
  private function buildTaskData(ScheduledTask $task) {
    $queue = $newsletter = null;
    if ($task->type === Sending::TASK_TYPE) {
      $queue = SendingQueue::where('task_id', $task->id)->findOne();
      $newsletter = $queue instanceof SendingQueue ? $queue->newsletter()->findOne() : null;
    }
    return [
      'id' => (int)$task->id,
      'type' => $task->type,
      'priority' => (int)$task->priority,
      'updated_at' => Carbon::createFromTimeString((string)$task->updatedAt)->timestamp,
      'scheduled_at' => $task->scheduledAt ? $task->scheduledAt : null,
      'status' => $task->status,
      'newsletter' => (($queue instanceof SendingQueue) && ($newsletter instanceof Newsletter)) ? [
        'newsletter_id' => (int)$queue->newsletterId,
        'queue_id' => (int)$queue->id,
        'subject' => $queue->newsletterRenderedSubject ?: $newsletter->subject,
        'preview_url' => NewsletterUrl::getViewInBrowserUrl(
          $newsletter,
          null,
          $queue
        ),
      ] : [
        'newsletter_id' => null,
        'queue_id' => null,
        'subject' => null,
        'preview_url' => null,
      ],
    ];
  }
}
