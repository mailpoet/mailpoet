<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\ResponseBuilders;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Newsletter\Sending\TimeZoneCampaignScheduler;

class SendingQueuesResponseBuilder {
  private ?TimeZoneCampaignScheduler $timeZoneCampaignScheduler;

  public function __construct(
    ?TimeZoneCampaignScheduler $timeZoneCampaignScheduler = null
  ) {
    $this->timeZoneCampaignScheduler = $timeZoneCampaignScheduler;
  }

  public function build(SendingQueueEntity $sendingQueue): array {
    $task = $sendingQueue->getTask();
    if (!$task instanceof ScheduledTaskEntity) {
      throw new \RuntimeException('Invalid state. SendingQueue has no ScheduledTask associated.');
    }

    $aggregateData = $this->timeZoneCampaignScheduler
      ? $this->timeZoneCampaignScheduler->getAggregateQueueData($sendingQueue)
      : null;
    $scheduledAt = $aggregateData['scheduledAt'] ?? $task->getScheduledAt();
    $processedAt = $aggregateData['processedAt'] ?? $task->getProcessedAt();

    return [
      'id' => $sendingQueue->getId(),
      'type' => $task->getType(),
      'status' => $aggregateData['status'] ?? $task->getStatus(),
      'priority' => $task->getPriority(),
      'scheduled_at' => $this->getFormattedDateOrNull($scheduledAt),
      'processed_at' => $this->getFormattedDateOrNull($processedAt),
      'created_at' => $this->getFormattedDateOrNull($task->getCreatedAt()),
      'updated_at' => $this->getFormattedDateOrNull($task->getUpdatedAt()),
      'deleted_at' => $this->getFormattedDateOrNull($task->getDeletedAt()),
      'in_progress' => $task->getInProgress(),
      'reschedule_count' => $task->getRescheduleCount(),
      'meta' => $aggregateData['meta'] ?? $sendingQueue->getMeta(),
      'task_id' => $task->getId(),
      'newsletter_id' => ($sendingQueue->getNewsletter() instanceof NewsletterEntity) ? $sendingQueue->getNewsletter()->getId() : null,
      'newsletter_rendered_body' => $sendingQueue->getNewsletterRenderedBody(),
      'newsletter_rendered_subject' => $sendingQueue->getNewsletterRenderedSubject(),
      'count_total' => $aggregateData['countTotal'] ?? $sendingQueue->getCountTotal(),
      'count_processed' => $aggregateData['countProcessed'] ?? $sendingQueue->getCountProcessed(),
      'count_to_process' => $aggregateData['countToProcess'] ?? $sendingQueue->getCountToProcess(),
    ];
  }

  private function getFormattedDateOrNull(?\DateTimeInterface $date): ?string {
    return $date ? $date->format('Y-m-d H:i:s') : null;
  }
}
