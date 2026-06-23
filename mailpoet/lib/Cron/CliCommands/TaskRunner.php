<?php declare(strict_types = 1);

namespace MailPoet\Cron\CliCommands;

use InvalidArgumentException;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\CronWorkerInterface;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Cron\Workers\StatsNotifications\Worker as StatsNotificationsWorker;
use MailPoet\Cron\Workers\WorkersFactory;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\InvalidStateException;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use RuntimeException;
use Throwable;

/**
 * Runs a MailPoet cron worker inside the current process.
 *
 * Bulk `run <type>` snapshots the type's currently-due tasks, then claims each as a CLI row (status
 * 'cli', invisible to the site daemon) and runs it once through the shared ClaimedTaskRunner — the same
 * claim model as `run --task-id`. This closes the concurrency hole (the daemon can never double-process
 * a row the CLI owns) and stops self-rescheduling batched workers from running away: continuations they
 * create mid-run are not in the snapshot, so each due task processes one batch and the continuation is
 * left for the site cron. Mailing workers run via process(), and 'sending' runs the Scheduler then the
 * SendingQueue. `run --task-id` claims one exact row through ClaimedTaskRunner. The 20-second execution
 * limit is lifted by default (or capped via $timeout).
 *
 * See doc/wp-cli-cron-commands.md for command behaviour and the cli-claim rationale.
 */
class TaskRunner {
  private WorkerTypesCatalog $workerTypesCatalog;

  private WorkersFactory $workersFactory;

  private ExecutionLimitOverride $executionLimitOverride;

  private ScheduledTasksRepository $scheduledTasksRepository;

  private ScheduledTaskResolver $taskResolver;

  private ClaimedTaskRunner $claimedTaskRunner;

  public function __construct(
    WorkerTypesCatalog $workerTypesCatalog,
    WorkersFactory $workersFactory,
    ExecutionLimitOverride $executionLimitOverride,
    ScheduledTasksRepository $scheduledTasksRepository,
    ScheduledTaskResolver $taskResolver,
    ClaimedTaskRunner $claimedTaskRunner
  ) {
    $this->workerTypesCatalog = $workerTypesCatalog;
    $this->workersFactory = $workersFactory;
    $this->executionLimitOverride = $executionLimitOverride;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->taskResolver = $taskResolver;
    $this->claimedTaskRunner = $claimedTaskRunner;
  }

  /**
   * @return array{completed: int, message: string, limit_reached: bool, backlog_drained: bool}
   */
  public function run(string $type, ?int $taskId = null, ?int $timeout = null): array {
    $this->workerTypesCatalog->assertValidType($type);

    if ($taskId !== null) {
      return $this->runClaimedTask($type, $taskId, $timeout);
    }

    if ($type === SendingQueueWorker::TASK_TYPE || $type === StatsNotificationsWorker::TASK_TYPE) {
      return $this->runMailing($type, $timeout);
    }

    return $this->runDueTasks($type, $timeout);
  }

  /**
   * --task-id: resolve the exact row (scheduled/paused only, type must match), claim it as a CLI row
   * preserving its meta, and run it through the shared ClaimedTaskRunner. The shared CronWorkerRunner
   * cannot see a STATUS_CLI row, so claiming is the only way the exact row is processed in-CLI.
   *
   * @return array{completed: int, message: string, limit_reached: bool, backlog_drained: bool}
   */
  private function runClaimedTask(string $type, int $taskId, ?int $timeout): array {
    $worker = $this->workerTypesCatalog->getWorkerByType($type);
    if ($worker === null) {
      throw new InvalidArgumentException("Task type '{$type}' has no runnable worker, so --task-id cannot run it in-process. Use `wp mailpoet cron trigger` for mailing types.");
    }

    $task = $this->taskResolver->resolveById($taskId, $type);
    $status = $task->getStatus();
    if (!in_array($status, [ScheduledTaskEntity::STATUS_SCHEDULED, ScheduledTaskEntity::STATUS_PAUSED], true)) {
      $current = $this->taskResolver->nameStatus($task);
      throw new InvalidArgumentException("Task {$taskId} is '{$current}' and cannot be run. Only scheduled or paused tasks can be run by ID.");
    }

    if (!$this->claimedTaskRunner->claimExisting($task)) {
      throw new RuntimeException(sprintf('Task %d was claimed by another process; nothing ran.', $taskId));
    }

    // ClaimedTaskRunner installs the execution-limit override itself, so the timeout is passed through
    // rather than wrapped here — wrapping would be defeated by its own (inner) override.
    $runResult = $this->claimedTaskRunner->run($worker, $task, $timeout);

    return [
      'completed' => $runResult['completed'] ? 1 : 0,
      'limit_reached' => $runResult['limit_reached'],
      'backlog_drained' => $runResult['completed'],
      'message' => $runResult['message'],
    ];
  }

  /**
   * Bulk `run <type>` for standard workers: snapshot the currently-due tasks, then claim and run each one
   * exactly once through the shared ClaimedTaskRunner. Tasks are claimed as 'cli' so the site daemon
   * cannot double-process them, and continuations created during the run (e.g. self-rescheduling batched
   * workers) are not in the snapshot, so they are left for the site cron instead of being chased.
   *
   * @return array{completed: int, message: string, limit_reached: bool, backlog_drained: bool}
   */
  private function runDueTasks(string $type, ?int $timeout): array {
    $worker = $this->resolveWorker($type);
    if ($worker === null) {
      // Should be unreachable: assertValidType allows only mailing + standard types, and mailing types
      // are handled before this method is called.
      throw new InvalidArgumentException("Task type '{$type}' has no runnable worker.");
    }

    // Pre-check requirements once so we never claim a task only to remove it on the requirements path.
    try {
      $requirementsMet = $worker->checkProcessingRequirements();
    } catch (Throwable $e) {
      throw new RuntimeException(sprintf("Requirements check for '%s' failed: %s. Nothing ran.", $type, $e->getMessage()), 0, $e);
    }
    if (!$requirementsMet) {
      // Nothing ran and the due tasks are left scheduled, so the backlog is not drained: surface a
      // warning rather than a green success.
      return [
        'completed' => 0,
        'limit_reached' => false,
        'backlog_drained' => false,
        'message' => sprintf("Ran '%s': requirements not met, nothing ran.", $type),
      ];
    }

    $dueTasks = $this->scheduledTasksRepository->findDueByType($type);

    $completed = 0;
    $handedBack = 0;
    $limitReached = false;
    $start = microtime(true);

    foreach ($dueTasks as $task) {
      // With --timeout, each task gets the run's remaining budget (capping the worker's own execution
      // limit too, not just the gap between tasks); once it is spent we stop starting new tasks.
      $remaining = null;
      if ($timeout !== null) {
        $remaining = $timeout - (microtime(true) - $start);
        if ($remaining <= 0) {
          $limitReached = true;
          break;
        }
      }

      // Atomic claim: another CLI run (or the daemon) may have taken this row since the snapshot, so a
      // lost claim is skipped rather than double-processed.
      if (!$this->claimedTaskRunner->claimExisting($task)) {
        continue;
      }

      // A worker failure aborts the bulk run: already-processed tasks stay done, the failing one is
      // handed back by ClaimedTaskRunner, and the RuntimeException propagates to the caller.
      $result = $this->claimedTaskRunner->run($worker, $task, $remaining === null ? null : (int)ceil($remaining));
      if ($result['limit_reached']) {
        // The task hit the cap and was handed back; stop the run so it is not counted as completed.
        $limitReached = true;
        break;
      }
      $result['completed'] ? $completed++ : $handedBack++;
    }

    // Handed-back tasks (partial work, not ready, or a failed worker) are not "drained" — the command
    // surfaces this as a warning so it is visible to operators and scripts.
    $backlogDrained = !$limitReached && $handedBack === 0;

    return [
      'completed' => $completed,
      'limit_reached' => $limitReached,
      'backlog_drained' => $backlogDrained,
      'message' => $this->buildMessage($type, $completed, $handedBack, $limitReached, $timeout),
    ];
  }

  /**
   * Mailing types ('sending', 'stats_notification') run their own mailer-driven flow instead of
   * CronWorkerInterface, via their own process step under the execution-limit override, surfacing a hit
   * limit as limit_reached.
   *
   * @return array{completed: int, message: string, limit_reached: bool, backlog_drained: bool}
   */
  private function runMailing(string $type, ?int $timeout): array {
    $limitReached = false;

    try {
      $this->executionLimitOverride->overrideDuring($timeout, function () use ($type): void {
        if ($type === SendingQueueWorker::TASK_TYPE) {
          $this->runSending();
          return;
        }
        $this->workersFactory->createStatsNotificationsWorker()->process();
      });
    } catch (\Exception $e) {
      if ($e->getCode() === CronHelper::DAEMON_EXECUTION_LIMIT_REACHED) {
        $limitReached = true;
      } else {
        throw $e;
      }
    }

    if ($limitReached) {
      return [
        'completed' => 0,
        'limit_reached' => true,
        'backlog_drained' => false,
        'message' => sprintf("Execution limit of %d seconds reached while running '%s'.", (int)$timeout, $type),
      ];
    }

    return [
      'completed' => 0,
      'limit_reached' => false,
      'backlog_drained' => true,
      'message' => sprintf("Ran '%s'.", $type),
    ];
  }

  /**
   * Seam over the catalog lookup so tests can drive the run with a stub worker.
   */
  protected function resolveWorker(string $type): ?CronWorkerInterface {
    return $this->workerTypesCatalog->getWorkerByType($type);
  }

  private function runSending(): void {
    // Scheduling first, then sending: the Scheduler turns scheduled newsletters into running sending
    // tasks the SendingQueue then picks up. Instantiated lazily because the queue worker resolves the
    // mailer eagerly and throws on a site with no sender configured.
    try {
      $scheduler = $this->workersFactory->createScheduleWorker();
      $queue = $this->workersFactory->createQueueWorker();
    } catch (InvalidStateException $e) {
      throw new RuntimeException('Sending is not configured on this site: ' . $e->getMessage());
    }

    $scheduler->process();
    $queue->process();
  }

  private function buildMessage(string $type, int $completed, int $handedBack, bool $limitReached, ?int $timeout): string {
    if ($limitReached) {
      return sprintf(
        "Execution limit of %d seconds reached while running '%s'. %d task(s) completed; remaining due tasks will run on the next invocation.",
        (int)$timeout,
        $type,
        $completed
      );
    }

    if ($handedBack > 0) {
      return sprintf(
        "Ran '%s': %d task(s) completed; %d task(s) handed back to the site cron (not ready or failed).",
        $type,
        $completed,
        $handedBack
      );
    }

    if ($completed > 0) {
      return sprintf("Ran '%s': %d task(s) completed.", $type, $completed);
    }

    return sprintf("Ran '%s': no tasks completed (nothing was due).", $type);
  }
}
