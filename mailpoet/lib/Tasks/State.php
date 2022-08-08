<?php

namespace MailPoet\Tasks;

use MailPoet\Cron\Workers\Scheduler;
use MailPoet\Models\ScheduledTask;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Url as NewsletterUrl;

class State {
  /** @var NewsletterUrl */
  private $newsletterUrl;

  /*** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  public function __construct(
    NewsletterUrl $newsletterUrl,
    SendingQueuesRepository $sendingQueuesRepository
  ) {
    $this->newsletterUrl = $newsletterUrl;
    $this->sendingQueuesRepository = $sendingQueuesRepository;
  }

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
    $limit = Scheduler::TASK_BATCH_SIZE
  ) {
    $tasks = [];
    foreach ($statuses as $status) {
      $query = ScheduledTask::orderByDesc('id')
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
      $queue = $this->sendingQueuesRepository->findOneBy(['task' => $task->id]);
      $newsletter = $queue ? $queue->getNewsletter() : null;
    }
    return [
      'id' => (int)$task->id,
      'type' => $task->type,
      'priority' => (int)$task->priority,
      'updated_at' => $task->updatedAt,
      'scheduled_at' => $task->scheduledAt ? $task->scheduledAt : null,
      'status' => $task->status,
      'newsletter' => $queue && $newsletter ? [
        'newsletter_id' => $newsletter->getId(),
        'queue_id' => $queue->getId(),
        'subject' => $queue->getNewsletterRenderedSubject() ?: $newsletter->getSubject(),
        'preview_url' => $this->newsletterUrl->getViewInBrowserUrl($newsletter, null, $queue),
      ] : [
        'newsletter_id' => null,
        'queue_id' => null,
        'subject' => null,
        'preview_url' => null,
      ],
    ];
  }
}
