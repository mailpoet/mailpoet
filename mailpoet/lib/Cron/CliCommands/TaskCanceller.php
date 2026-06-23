<?php declare(strict_types = 1);

namespace MailPoet\Cron\CliCommands;

use InvalidArgumentException;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoetVendor\Carbon\Carbon;

class TaskCanceller {
  private ScheduledTasksRepository $scheduledTasksRepository;

  private ScheduledTaskResolver $taskResolver;

  public function __construct(
    ScheduledTasksRepository $scheduledTasksRepository,
    ScheduledTaskResolver $taskResolver
  ) {
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->taskResolver = $taskResolver;
  }

  /**
   * Cancels a scheduled, paused, or 'cli' task. Running tasks are owned by their executor and completed
   * ones are history, so both are rejected. A 'cli' task is cancellable as the recovery path for a
   * zombie left behind by a hard-killed CLI run (cancel, then re-add).
   *
   * @return array{id: int, type: string}
   */
  public function cancel(int $taskId): array {
    $task = $this->taskResolver->resolveById($taskId);

    $status = $task->getStatus();
    if (!in_array($status, [ScheduledTaskEntity::STATUS_SCHEDULED, ScheduledTaskEntity::STATUS_PAUSED, ScheduledTaskEntity::STATUS_CLI], true)) {
      $current = $this->taskResolver->nameStatus($task);
      throw new InvalidArgumentException("Task {$taskId} is '{$current}' and cannot be cancelled. Only scheduled, paused, or cli tasks can be cancelled.");
    }

    $task->setStatus(ScheduledTaskEntity::STATUS_CANCELLED);
    $task->setCancelledAt(Carbon::now()->millisecond(0));
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();

    return [
      'id' => (int)$task->getId(),
      'type' => (string)$task->getType(),
    ];
  }
}
