<?php declare(strict_types = 1);

namespace MailPoet\Cron\CliCommands;

use Exception;
use InvalidArgumentException;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoetVendor\Carbon\Carbon;

/**
 * Adds a new scheduled task for a standard cron worker, optionally claiming and running it in-process.
 *
 * Only standard CronWorkerInterface workers are addable (see WorkerTypesCatalog::getAddableTypes());
 * mailing 'sending'/'stats_notification' rows are created by app flows and are rejected. --run drives a
 * freshly claimed row through ClaimedTaskRunner, shared with `cron run --task-id`.
 *
 * See doc/wp-cli-cron-commands.md for command behaviour.
 */
class TaskAdder {
  const PRIORITY_MAP = [
    'high' => ScheduledTaskEntity::PRIORITY_HIGH,
    'medium' => ScheduledTaskEntity::PRIORITY_MEDIUM,
    'low' => ScheduledTaskEntity::PRIORITY_LOW,
  ];

  private WorkerTypesCatalog $workerTypesCatalog;

  private ScheduledTasksRepository $scheduledTasksRepository;

  private ClaimedTaskRunner $claimedTaskRunner;

  public function __construct(
    WorkerTypesCatalog $workerTypesCatalog,
    ScheduledTasksRepository $scheduledTasksRepository,
    ClaimedTaskRunner $claimedTaskRunner
  ) {
    $this->workerTypesCatalog = $workerTypesCatalog;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->claimedTaskRunner = $claimedTaskRunner;
  }

  /**
   * @return array{id: int, type: string, action: string, message: string, run: array{completed: bool, limit_reached: bool, message: string}|null}
   */
  public function add(string $type, ?string $at, ?int $in, string $priority, bool $force, bool $run): array {
    $this->workerTypesCatalog->assertAddableType($type);
    $priorityValue = $this->resolvePriority($priority);

    if ($run && ($at !== null || $in !== null)) {
      throw new InvalidArgumentException('--run cannot be combined with --at or --in. A claimed task runs immediately.');
    }

    if ($run) {
      return $this->claimAndRun($type, $priorityValue);
    }

    $scheduledAt = $this->resolveScheduledAt($at, $in);

    if (!$force) {
      $existing = $this->scheduledTasksRepository->findScheduledTask($type);
      if ($existing instanceof ScheduledTaskEntity) {
        $existingId = (int)$existing->getId();
        return [
          'id' => $existingId,
          'type' => $type,
          'action' => 'duplicate',
          'message' => sprintf("A task of type '%s' is already scheduled as task %d. Use --force to add another.", $type, $existingId),
          'run' => null,
        ];
      }
    }

    $task = $this->createTask($type, $priorityValue, $scheduledAt);

    return [
      'id' => (int)$task->getId(),
      'type' => $type,
      'action' => 'created',
      'message' => sprintf("Added task %d (%s), scheduled for %s, priority %s.", $task->getId(), $type, $scheduledAt->format('Y-m-d H:i:s'), $priority),
      'run' => null,
    ];
  }

  private function createTask(string $type, int $priority, Carbon $scheduledAt): ScheduledTaskEntity {
    $task = new ScheduledTaskEntity();
    $task->setType($type);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $task->setPriority($priority);
    $task->setScheduledAt($scheduledAt);
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();
    return $task;
  }

  /**
   * Claims a fresh row (status STATUS_CLI) and processes exactly that one task. The duplicate check is
   * intentionally skipped: the claim is a fresh, independently-owned row, never a "scheduled
   * duplicate", so --run ignores --force entirely.
   *
   * @return array{id: int, type: string, action: string, message: string, run: array{completed: bool, limit_reached: bool, message: string}}
   */
  private function claimAndRun(string $type, int $priority): array {
    $worker = $this->workerTypesCatalog->getWorkerByType($type);
    if ($worker === null) {
      // Unreachable: assertAddableType already restricts to types with a standard worker.
      throw new InvalidArgumentException("Task type '{$type}' has no runnable worker.");
    }

    $task = $this->claimedTaskRunner->claimNew($type, $priority);
    $taskId = (int)$task->getId();

    $runResult = $this->claimedTaskRunner->run($worker, $task);

    return [
      'id' => $taskId,
      'type' => $type,
      'action' => 'claimed',
      'message' => sprintf("Claimed task %d (%s) and ran it in this process.", $taskId, $type),
      'run' => $runResult,
    ];
  }

  private function resolveScheduledAt(?string $at, ?int $in): Carbon {
    if ($at !== null && $in !== null) {
      throw new InvalidArgumentException('--at and --in cannot be used together. Pick one.');
    }

    if ($at !== null) {
      try {
        return Carbon::parse($at)->millisecond(0);
      } catch (Exception $e) {
        throw new InvalidArgumentException("Could not parse --at value '{$at}'. Use a date/time like '2026-01-01 09:00' or 'tomorrow 8am'.");
      }
    }

    if ($in !== null) {
      return Carbon::now()->millisecond(0)->addSeconds($in);
    }

    return Carbon::now()->millisecond(0);
  }

  private function resolvePriority(string $priority): int {
    if (!isset(self::PRIORITY_MAP[$priority])) {
      $valid = implode(', ', array_keys(self::PRIORITY_MAP));
      throw new InvalidArgumentException("Invalid priority '{$priority}'. Valid values: {$valid}.");
    }
    return self::PRIORITY_MAP[$priority];
  }
}
