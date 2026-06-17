<?php declare(strict_types = 1);

namespace MailPoet\Cron\CliCommands;

use MailPoet\Cron\CronHelper;
use MailPoet\Cron\CronWorkerInterface;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoetVendor\Carbon\Carbon;
use RuntimeException;
use Throwable;

/**
 * Claims a single scheduled-task row for a WP-CLI process and drives it through one worker run.
 *
 * A claimed row carries status = STATUS_CLI, which is invisible to every daemon-side query, so the web
 * daemon can neither pick it up nor reschedule it while the CLI run owns it. On success the row is
 * completed (with a meta.cli breadcrumb merged after the worker's last write); on a partial run or
 * failure it is handed back to the site cron (scheduled, due now); if requirements are not met it is
 * removed.
 *
 * See doc/wp-cli-cron-commands.md ("CLI execution: the cli status") for the full rationale — why a
 * dedicated status, the meta/inProgress placement, and how hard-killed (zombie) claims are handled.
 */
class ClaimedTaskRunner {
  private ScheduledTasksRepository $scheduledTasksRepository;

  private ExecutionLimitOverride $executionLimitOverride;

  public function __construct(
    ScheduledTasksRepository $scheduledTasksRepository,
    ExecutionLimitOverride $executionLimitOverride
  ) {
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->executionLimitOverride = $executionLimitOverride;
  }

  /**
   * Creates a fresh row already claimed (status STATUS_CLI), due now, and flushes it. No meta and no
   * inProgress: the status alone hides the row, and workers may overwrite meta mid-run (see the doc).
   * Public so a test can inspect the DB state after the claim.
   */
  public function claimNew(string $type, int $priority = ScheduledTaskEntity::PRIORITY_LOW): ScheduledTaskEntity {
    $task = new ScheduledTaskEntity();
    $task->setType($type);
    $task->setStatus(ScheduledTaskEntity::STATUS_CLI);
    $task->setPriority($priority);
    $task->setScheduledAt(Carbon::now()->millisecond(0));
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();
    return $task;
  }

  /**
   * Atomically claims an EXISTING row (scheduled/paused, already resolved by the caller) by transitioning
   * it to STATUS_CLI in a single guarded UPDATE. The row's meta is preserved; only the status changes.
   * Returns false when the row was no longer claimable (a concurrent CLI run or the site daemon got it
   * first), so the caller can skip it instead of double-processing.
   */
  public function claimExisting(ScheduledTaskEntity $task): bool {
    return $this->scheduledTasksRepository->claimAsCli($task);
  }

  /**
   * Drives one already-claimed row through a single worker run. The claim is always resolved on a
   * non-completing path so the row never stays in 'cli' unless the process dies: on success it is
   * completed; on partial work, a failed prepare, a hit execution limit, or an error it is handed back to
   * the site cron; when requirements are not met it is removed. Public so a test can drive a fake worker
   * through each path directly.
   *
   * $timeout caps the worker's own execution-limit checks (in seconds); null lifts the cap so the worker
   * runs to completion. A worker that hits the cap is handed back gracefully and reported via
   * limit_reached rather than as a failure.
   *
   * @return array{completed: bool, limit_reached: bool, message: string}
   */
  public function run(CronWorkerInterface $worker, ScheduledTaskEntity $task, ?int $timeout = null): array {
    // Capture the claim marker at the start of the run. Claim and run always happen back-to-back on
    // the same instance, so this is the pid/started_at of the run that owns the row.
    $marker = [
      'pid' => getmypid(),
      'started_at' => Carbon::now()->toIso8601String(),
    ];

    $requirementsMet = false;
    try {
      $requirementsMet = $worker->checkProcessingRequirements();
    } catch (Throwable $e) {
      $this->handBack($task);
      throw new RuntimeException(sprintf("Task %d (%s) failed while running: %s. It was handed back to the site cron to retry.", $task->getId(), $worker->getTaskType(), $e->getMessage()), 0, $e);
    }

    if (!$requirementsMet) {
      // Requirements not met: the claimed row would never run, so drop it rather than leave a stuck
      // 'cli' task. Mirrors CronWorkerRunner, which removes such tasks.
      $this->scheduledTasksRepository->remove($task);
      $this->scheduledTasksRepository->flush();
      throw new RuntimeException(sprintf("Requirements for '%s' are not met; the claimed task was removed and nothing ran.", $worker->getTaskType()));
    }

    $completed = false;
    try {
      $worker->init();

      $this->executionLimitOverride->overrideDuring($timeout, function () use ($worker, $task, &$completed): void {
        // Mirror CronWorkerRunner: a task is only processed after prepare succeeds. Bounce is the one
        // standard worker that overrides prepare (it builds subscriber rows and returns false when there
        // is nothing to do); when prepare returns false we leave $completed false and hand the row back.
        if (!$worker->prepareTaskStrategy($task, microtime(true))) {
          return;
        }
        $completed = (bool)$worker->processTaskStrategy($task, microtime(true));
      });
    } catch (Throwable $e) {
      $this->handBack($task);
      // A worker that hit the execution limit (when $timeout caps the run) is yielding, not failing:
      // hand it back so the site cron continues it, and report it as limit_reached rather than an error.
      if ($e->getCode() === CronHelper::DAEMON_EXECUTION_LIMIT_REACHED) {
        return [
          'completed' => false,
          'limit_reached' => true,
          'message' => sprintf('Task %d hit the execution limit; it was handed back to the site cron to continue.', $task->getId()),
        ];
      }
      throw new RuntimeException(sprintf("Task %d (%s) failed while running: %s. It was handed back to the site cron to retry.", $task->getId(), $worker->getTaskType(), $e->getMessage()), 0, $e);
    }

    if ($completed) {
      $this->complete($task, $marker);
      return [
        'completed' => true,
        'limit_reached' => false,
        'message' => sprintf('Task %d completed.', $task->getId()),
      ];
    }

    $this->handBack($task);
    return [
      'completed' => false,
      'limit_reached' => false,
      'message' => sprintf('Task %d processed partially; it was handed back to the site cron to continue.', $task->getId()),
    ];
  }

  /**
   * Marks the row completed (STATUS_COMPLETED + processedAt) and stamps meta.cli as the permanent
   * "done by CLI" breadcrumb. The merge happens AFTER the worker's last write, so a worker that
   * overwrote meta wholesale mid-run cannot clobber the breadcrumb.
   *
   * @param array{pid: int|false, started_at: string} $marker
   */
  private function complete(ScheduledTaskEntity $task, array $marker): void {
    $task->setProcessedAt(Carbon::now()->millisecond(0));
    $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $task->setMeta(array_merge($task->getMeta() ?? [], ['cli' => $marker]));
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();
  }

  /**
   * Hands a not-finished row back to the site cron: STATUS_SCHEDULED, due now. No meta.cli is written
   * — the breadcrumb is only for tasks the CLI actually completed.
   */
  private function handBack(ScheduledTaskEntity $task): void {
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $task->setScheduledAt(Carbon::now()->millisecond(0));
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();
  }
}
