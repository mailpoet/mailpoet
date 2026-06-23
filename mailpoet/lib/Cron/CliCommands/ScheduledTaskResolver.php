<?php declare(strict_types = 1);

namespace MailPoet\Cron\CliCommands;

use InvalidArgumentException;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;

class ScheduledTaskResolver {
  private ScheduledTasksRepository $scheduledTasksRepository;

  public function __construct(
    ScheduledTasksRepository $scheduledTasksRepository
  ) {
    $this->scheduledTasksRepository = $scheduledTasksRepository;
  }

  /**
   * Finds a task by ID, treating soft-deleted rows as missing. When $expectedType is given the row
   * must match it. The status whitelist stays with each caller.
   */
  public function resolveById(int $taskId, ?string $expectedType = null): ScheduledTaskEntity {
    $task = $this->scheduledTasksRepository->findOneById($taskId);
    if (!$task instanceof ScheduledTaskEntity || $task->getDeletedAt() !== null) {
      throw new InvalidArgumentException("No task with ID {$taskId} found.");
    }

    if ($expectedType !== null && $task->getType() !== $expectedType) {
      $actualType = (string)$task->getType();
      throw new InvalidArgumentException("Task {$taskId} is of type '{$actualType}', not '{$expectedType}'.");
    }

    return $task;
  }

  /**
   * The displayable status, mapping the NULL "running" placeholder to its virtual name.
   */
  public function nameStatus(ScheduledTaskEntity $task): string {
    return $task->getStatus() ?? ScheduledTaskEntity::VIRTUAL_STATUS_RUNNING;
  }
}
