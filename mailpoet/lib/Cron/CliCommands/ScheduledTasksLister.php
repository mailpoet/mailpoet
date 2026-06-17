<?php declare(strict_types = 1);

namespace MailPoet\Cron\CliCommands;

use InvalidArgumentException;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;

class ScheduledTasksLister {
  const DEFAULT_LIMIT = 50;

  const DEFAULT_STATUSES = [
    ScheduledTaskEntity::STATUS_SCHEDULED,
    ScheduledTaskEntity::VIRTUAL_STATUS_RUNNING,
    ScheduledTaskEntity::STATUS_CLI,
  ];

  const ALLOWED_STATUSES = [
    ScheduledTaskEntity::STATUS_SCHEDULED,
    ScheduledTaskEntity::VIRTUAL_STATUS_RUNNING,
    ScheduledTaskEntity::STATUS_CLI,
    ScheduledTaskEntity::STATUS_COMPLETED,
    ScheduledTaskEntity::STATUS_CANCELLED,
    ScheduledTaskEntity::STATUS_PAUSED,
    ScheduledTaskEntity::STATUS_INVALID,
  ];

  const FIELDS = ['id', 'type', 'status', 'scheduled_at', 'priority', 'updated_at'];

  private ScheduledTasksRepository $scheduledTasksRepository;

  public function __construct(
    ScheduledTasksRepository $scheduledTasksRepository
  ) {
    $this->scheduledTasksRepository = $scheduledTasksRepository;
  }

  /**
   * @param string|null $status One of ALLOWED_STATUSES, 'all', or null for the default actionable statuses.
   * @return array<int, array{id: int|null, type: string|null, status: string, scheduled_at: string, priority: int, updated_at: string}>
   */
  public function getRows(?string $status = null, ?string $type = null, int $limit = self::DEFAULT_LIMIT): array {
    if ($limit < 1) {
      throw new InvalidArgumentException("Invalid limit '{$limit}'. It must be a positive integer.");
    }

    $statuses = $this->resolveStatuses($status);
    $tasks = $this->scheduledTasksRepository->getLatestTasks($type, $statuses, $limit);

    $rows = array_map([$this, 'mapTaskToRow'], $tasks);

    usort($rows, function (array $a, array $b): int {
      return ($b['id'] ?? 0) <=> ($a['id'] ?? 0);
    });

    return array_slice($rows, 0, $limit);
  }

  /**
   * @return string[]
   */
  private function resolveStatuses(?string $status): array {
    if ($status === null) {
      return self::DEFAULT_STATUSES;
    }

    if ($status === 'all') {
      return self::ALLOWED_STATUSES;
    }

    if (!in_array($status, self::ALLOWED_STATUSES, true)) {
      $allowed = implode(', ', array_merge(self::ALLOWED_STATUSES, ['all']));
      throw new InvalidArgumentException("Invalid status '{$status}'. Allowed values: {$allowed}.");
    }

    return [$status];
  }

  /**
   * @return array{id: int|null, type: string|null, status: string, scheduled_at: string, priority: int, updated_at: string}
   */
  private function mapTaskToRow(ScheduledTaskEntity $task): array {
    $status = $this->renderStatus($task);
    $scheduledAt = $task->getScheduledAt();
    $updatedAt = $task->getUpdatedAt();

    return [
      'id' => $task->getId(),
      'type' => $task->getType(),
      'status' => $status,
      'scheduled_at' => $scheduledAt ? $scheduledAt->format('Y-m-d H:i:s') : '',
      'priority' => $task->getPriority(),
      'updated_at' => $updatedAt ? $updatedAt->format('Y-m-d H:i:s') : '',
    ];
  }

  /**
   * Maps the NULL "running" placeholder to its virtual name. A CLI-claimed task carries the literal
   * 'cli' status, so it renders as 'cli' through the regular branch with no special handling here.
   */
  private function renderStatus(ScheduledTaskEntity $task): string {
    return $task->getStatus() ?? ScheduledTaskEntity::VIRTUAL_STATUS_RUNNING;
  }
}
