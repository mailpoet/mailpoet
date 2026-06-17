<?php declare(strict_types = 1);

namespace MailPoet\Cron\CliCommands;

use InvalidArgumentException;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoetVendor\Carbon\Carbon;

class TaskTrigger {
  private ScheduledTasksRepository $scheduledTasksRepository;

  private WorkerTypesCatalog $workerTypesCatalog;

  private ScheduledTaskResolver $taskResolver;

  public function __construct(
    ScheduledTasksRepository $scheduledTasksRepository,
    WorkerTypesCatalog $workerTypesCatalog,
    ScheduledTaskResolver $taskResolver
  ) {
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->workerTypesCatalog = $workerTypesCatalog;
    $this->taskResolver = $taskResolver;
  }

  /**
   * Marks a task as due now so the site's own cron processor picks it up. Does not kick the pipeline.
   *
   * @return array{id: int, type: string}
   */
  public function trigger(string $type, ?int $taskId = null): array {
    $this->workerTypesCatalog->assertValidType($type);

    $task = $taskId !== null
      ? $this->resolveTaskById($taskId, $type)
      : $this->resolveTaskByType($type);

    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $task->setScheduledAt(Carbon::now()->millisecond(0));
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();

    return [
      'id' => (int)$task->getId(),
      'type' => (string)$task->getType(),
    ];
  }

  private function resolveTaskByType(string $type): ScheduledTaskEntity {
    $task = $this->scheduledTasksRepository->findSoonestScheduledTaskByType($type);
    if (!$task instanceof ScheduledTaskEntity) {
      throw new InvalidArgumentException("No scheduled task of type '{$type}' found. Run `wp mailpoet cron list` to see existing tasks.");
    }
    return $task;
  }

  private function resolveTaskById(int $taskId, string $type): ScheduledTaskEntity {
    $task = $this->taskResolver->resolveById($taskId, $type);

    $status = $task->getStatus();
    if (!in_array($status, [ScheduledTaskEntity::STATUS_SCHEDULED, ScheduledTaskEntity::STATUS_PAUSED], true)) {
      $current = $this->taskResolver->nameStatus($task);
      throw new InvalidArgumentException("Task {$taskId} is '{$current}' and cannot be triggered. Only scheduled or paused tasks can be triggered.");
    }

    return $task;
  }
}
