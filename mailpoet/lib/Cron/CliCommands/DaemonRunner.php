<?php declare(strict_types = 1);

namespace MailPoet\Cron\CliCommands;

use MailPoet\Cron\CronHelper;
use MailPoet\Cron\CronWorkerRunner;
use MailPoet\Cron\CronWorkerScheduler;
use MailPoet\Cron\Daemon;
use MailPoet\Cron\Workers\WorkersFactory;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoetVendor\Doctrine\ORM\EntityManager;

/**
 * Runs one full MailPoet daemon pass over all workers inside the current process.
 *
 * This is a single Daemon::run() pass: each worker runs once, processing its due tasks (a standard
 * worker processes at most TASK_BATCH_SIZE tasks per run). It is not a backlog drain — large
 * backlogs may need several passes or a targeted `wp mailpoet cron run <type>`. The daemon's own
 * maintenance-mode early exit is preserved. The 20-second execution limit is lifted by default (or
 * capped via $timeout); because the daemon sets its timer at construction, the cap applies to the
 * whole pass with no extra tracking.
 */
class DaemonRunner {
  private ExecutionLimitOverride $executionLimitOverride;

  private CronHelper $cronHelper;

  private CronWorkerScheduler $cronWorkerScheduler;

  private ScheduledTasksRepository $scheduledTasksRepository;

  private EntityManager $entityManager;

  private LoggerFactory $loggerFactory;

  private WorkersFactory $workersFactory;

  public function __construct(
    ExecutionLimitOverride $executionLimitOverride,
    CronHelper $cronHelper,
    CronWorkerScheduler $cronWorkerScheduler,
    ScheduledTasksRepository $scheduledTasksRepository,
    EntityManager $entityManager,
    LoggerFactory $loggerFactory,
    WorkersFactory $workersFactory
  ) {
    $this->executionLimitOverride = $executionLimitOverride;
    $this->cronHelper = $cronHelper;
    $this->cronWorkerScheduler = $cronWorkerScheduler;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->entityManager = $entityManager;
    $this->loggerFactory = $loggerFactory;
    $this->workersFactory = $workersFactory;
  }

  /**
   * @return array{errors: array<array{worker: string, message: string}>}
   */
  public function run(?int $timeout = null): array {
    // createDaemon() resets last_error to null, so any last_error read back after the run is from
    // this pass alone. Mirrors how the web cron (DaemonRun::process) builds its settings data.
    $settingsDaemonData = $this->cronHelper->createDaemon($this->cronHelper->createToken());

    $this->executionLimitOverride->overrideDuring($timeout, function () use ($settingsDaemonData): void {
      $this->makeDaemon()->run($settingsDaemonData);
    });

    return [
      'errors' => $this->readErrors(),
    ];
  }

  /**
   * @return array<array{worker: string, message: string}>
   */
  private function readErrors(): array {
    $daemon = $this->cronHelper->getDaemon();
    $errors = is_array($daemon) ? ($daemon['last_error'] ?? null) : null;
    if (!is_array($errors)) {
      return [];
    }

    // last_error is persisted untyped; normalise to the worker/message shape callers expect.
    $normalised = [];
    foreach ($errors as $error) {
      $worker = is_array($error) ? ($error['worker'] ?? '') : '';
      $message = is_array($error) ? ($error['message'] ?? '') : '';
      $normalised[] = [
        'worker' => is_string($worker) ? $worker : '',
        'message' => is_string($message) ? $message : '',
      ];
    }
    return $normalised;
  }

  /**
   * A fresh Daemon (and a fresh CronWorkerRunner) per invocation so both execution timers start at
   * the run, not at container build. Otherwise the shared instances would carry a stale timer and a
   * --timeout cap would be measured from the wrong moment. Protected so a test can substitute a
   * daemon that persists a worker error and assert the error-retrieval path surfaces it.
   */
  protected function makeDaemon(): Daemon {
    $cronWorkerRunner = new CronWorkerRunner(
      $this->cronHelper,
      $this->cronWorkerScheduler,
      $this->scheduledTasksRepository,
      $this->loggerFactory
    );

    return new Daemon(
      $this->cronHelper,
      $cronWorkerRunner,
      $this->entityManager,
      $this->workersFactory,
      $this->loggerFactory
    );
  }
}
