<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\CliCommands;

use InvalidArgumentException;
use MailPoet\Cron\CliCommands\ClaimedTaskRunner;
use MailPoet\Cron\CliCommands\ExecutionLimitOverride;
use MailPoet\Cron\CliCommands\ScheduledTaskResolver;
use MailPoet\Cron\CliCommands\TaskRunner;
use MailPoet\Cron\CliCommands\WorkerTypesCatalog;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\CronWorkerInterface;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Mailer as MailerTask;
use MailPoet\Cron\Workers\StatsNotifications\Worker as StatsNotificationsWorker;
use MailPoet\Cron\Workers\UnsubscribeTokens;
use MailPoet\Cron\Workers\WorkersFactory;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\SendingQueue as SendingQueueFactory;
use MailPoetVendor\Carbon\Carbon;
use RuntimeException;

class TaskRunnerTest extends \MailPoetTest {
  /** @var TaskRunner */
  private $runner;

  /** @var ScheduledTaskFactory */
  private $taskFactory;

  /** @var SettingsController */
  private $settings;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  public function _before() {
    parent::_before();
    $this->runner = $this->diContainer->get(TaskRunner::class);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->taskFactory = new ScheduledTaskFactory();
  }

  public function testItRunsAStandardWorkerDueTaskToCompletion() {
    $task = $this->taskFactory->create(UnsubscribeTokens::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now());

    $result = $this->runner->run(UnsubscribeTokens::TASK_TYPE);

    verify($result['completed'])->equals(1);
    verify($result['limit_reached'])->false();
    verify($result['message'])->stringContainsString('1 task(s) completed');

    $this->entityManager->refresh($task);
    verify($task->getStatus())->same(ScheduledTaskEntity::STATUS_COMPLETED);
  }

  public function testItReportsWhenNothingIsDue() {
    $result = $this->runner->run(UnsubscribeTokens::TASK_TYPE);

    verify($result['completed'])->equals(0);
    verify($result['limit_reached'])->false();
    verify($result['message'])->stringContainsString('no tasks completed');
  }

  public function testItRunsExactlyTheTaskGivenByTaskId() {
    // Scheduled in the future so it would not be due on its own; --task-id claims and runs it now.
    $task = $this->taskFactory->create(UnsubscribeTokens::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->addDays(5));

    $result = $this->runner->run(UnsubscribeTokens::TASK_TYPE, (int)$task->getId());

    verify($result['completed'])->equals(1);
    $this->entityManager->clear();
    $persisted = $this->scheduledTasksRepository->findOneById((int)$task->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $persisted);
    verify($persisted->getStatus())->same(ScheduledTaskEntity::STATUS_COMPLETED);
    // Completed via the claim path: the cli breadcrumb is stamped.
    $meta = $persisted->getMeta();
    $this->assertIsArray($meta);
    $this->assertArrayHasKey('cli', $meta);
  }

  public function testItClaimsAnExistingScheduledRowPreservingItsMetaWhenRunByTaskId() {
    $task = $this->taskFactory->create(UnsubscribeTokens::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->addDays(5));
    $task->setMeta(['kept' => 'yes']);
    $this->entityManager->flush();

    $result = $this->runner->run(UnsubscribeTokens::TASK_TYPE, (int)$task->getId());

    verify($result['completed'])->equals(1);
    $this->entityManager->clear();
    $persisted = $this->scheduledTasksRepository->findOneById((int)$task->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $persisted);
    verify($persisted->getStatus())->same(ScheduledTaskEntity::STATUS_COMPLETED);
    $meta = $persisted->getMeta();
    $this->assertIsArray($meta);
    // The pre-existing meta survives the claim and merges with the cli breadcrumb.
    verify($meta['kept'])->same('yes');
    $this->assertArrayHasKey('cli', $meta);
  }

  public function testItRunsAPausedTaskByTaskId() {
    $task = $this->taskFactory->create(UnsubscribeTokens::TASK_TYPE, ScheduledTaskEntity::STATUS_PAUSED, Carbon::now());

    $result = $this->runner->run(UnsubscribeTokens::TASK_TYPE, (int)$task->getId());

    verify($result['completed'])->equals(1);
    $this->entityManager->clear();
    $persisted = $this->scheduledTasksRepository->findOneById((int)$task->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $persisted);
    verify($persisted->getStatus())->same(ScheduledTaskEntity::STATUS_COMPLETED);
  }

  public function testItRejectsRunningACompletedTaskByTaskId() {
    $task = $this->taskFactory->create(UnsubscribeTokens::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now());

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/cannot be run/');
    $this->runner->run(UnsubscribeTokens::TASK_TYPE, (int)$task->getId());
  }

  public function testItRejectsRunningARunningTaskByTaskId() {
    $task = $this->taskFactory->create(UnsubscribeTokens::TASK_TYPE, null, Carbon::now());

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/running.*cannot be run/');
    $this->runner->run(UnsubscribeTokens::TASK_TYPE, (int)$task->getId());
  }

  public function testItRejectsRunningAMailingTypeByTaskId() {
    $task = $this->taskFactory->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now());

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/no runnable worker/');
    $this->runner->run(SendingQueueWorker::TASK_TYPE, (int)$task->getId());
  }

  public function testItDrainsEveryDueTaskInTheSnapshot() {
    // Bulk run snapshots the currently-due tasks and processes them all in one pass (no batch cap).
    $tasks = [];
    for ($i = 0; $i < 3; $i++) {
      $tasks[] = $this->taskFactory->create(UnsubscribeTokens::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now());
    }

    $result = $this->runner->run(UnsubscribeTokens::TASK_TYPE);

    verify($result['completed'])->equals(3);
    verify($result['limit_reached'])->false();
    verify($result['backlog_drained'])->true();

    foreach ($tasks as $task) {
      $this->entityManager->refresh($task);
      verify($task->getStatus())->same(ScheduledTaskEntity::STATUS_COMPLETED);
    }
  }

  public function testBulkRunProcessesEachTaskThroughTheCliClaim() {
    // The bulk path must claim each row as 'cli' (not leave it at NULL/running) before processing, so
    // a concurrent site daemon cannot pick it up. The stub records the status it sees at process time.
    $task = $this->taskFactory->create(UnsubscribeTokens::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now());

    $worker = $this->makeStatusRecordingWorker(UnsubscribeTokens::TASK_TYPE);
    $runner = $this->makeTaskRunnerWithWorker($worker);

    $result = $runner->run(UnsubscribeTokens::TASK_TYPE);

    verify($result['completed'])->equals(1);
    verify($worker->seenStatuses)->equals([ScheduledTaskEntity::STATUS_CLI]);

    $this->entityManager->refresh($task);
    verify($task->getStatus())->same(ScheduledTaskEntity::STATUS_COMPLETED);
  }

  public function testBulkRunDoesNotChaseSelfRescheduledContinuations() {
    // KEY REGRESSION: a self-rescheduling batched worker (like subscribers_engagement_score) completes
    // its task and schedules a new due-now continuation. The bulk run must process only the snapshotted
    // task and leave the single continuation for the site cron, NOT loop and drain the whole dataset.
    $original = $this->taskFactory->create(UnsubscribeTokens::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now());

    $worker = $this->makeSelfReschedulingWorker(UnsubscribeTokens::TASK_TYPE);
    $runner = $this->makeTaskRunnerWithWorker($worker);

    $result = $runner->run(UnsubscribeTokens::TASK_TYPE);

    // Exactly one task processed (the original), not a runaway over the continuation chain.
    verify($result['completed'])->equals(1);
    verify($worker->processCount)->equals(1);

    $this->entityManager->refresh($original);
    verify($original->getStatus())->same(ScheduledTaskEntity::STATUS_COMPLETED);

    // Exactly one continuation left, still scheduled (untouched), for the site cron to pick up.
    $scheduled = $this->scheduledTasksRepository->findDueByType(UnsubscribeTokens::TASK_TYPE);
    verify(count($scheduled))->equals(1);
    verify($scheduled[0]->getId())->notEquals((int)$original->getId());
    verify($scheduled[0]->getStatus())->same(ScheduledTaskEntity::STATUS_SCHEDULED);
  }

  public function testBulkRunDoesNotClaimAnythingWhenRequirementsAreNotMet() {
    // Requirements are pre-checked once before claiming, so a due task is left untouched (scheduled)
    // when the worker's requirements are not met — nothing is claimed or removed.
    $task = $this->taskFactory->create(UnsubscribeTokens::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now());

    $worker = $this->makeRequirementsNotMetWorker(UnsubscribeTokens::TASK_TYPE);
    $runner = $this->makeTaskRunnerWithWorker($worker);

    $result = $runner->run(UnsubscribeTokens::TASK_TYPE);

    verify($result['completed'])->equals(0);
    verify($result['limit_reached'])->false();
    // Nothing ran and the due task is left scheduled, so the backlog is not drained (command warns).
    verify($result['backlog_drained'])->false();
    verify($result['message'])->stringContainsString('requirements not met');

    $this->entityManager->refresh($task);
    // Still scheduled and present: nothing was claimed or removed.
    verify($task->getStatus())->same(ScheduledTaskEntity::STATUS_SCHEDULED);
  }

  public function testItRunsOnlyTheTargetedTaskByTaskIdLeavingOtherDueRowsUntouched() {
    // --task-id runs exactly one row via the claim path; the other due rows are left for the daemon
    // (or a bulk `run <type>`). This is the deliberate contrast with the no-task-id backlog drain.
    $others = [];
    for ($i = 0; $i < 3; $i++) {
      $others[] = $this->taskFactory->create(UnsubscribeTokens::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now());
    }
    $targeted = $this->taskFactory->create(UnsubscribeTokens::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->addDays(5));

    $result = $this->runner->run(UnsubscribeTokens::TASK_TYPE, (int)$targeted->getId());

    verify($result['completed'])->equals(1);
    verify($result['limit_reached'])->false();

    $this->entityManager->clear();
    $persistedTargeted = $this->scheduledTasksRepository->findOneById((int)$targeted->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $persistedTargeted);
    verify($persistedTargeted->getStatus())->same(ScheduledTaskEntity::STATUS_COMPLETED);

    foreach ($others as $other) {
      $persistedOther = $this->scheduledTasksRepository->findOneById((int)$other->getId());
      $this->assertInstanceOf(ScheduledTaskEntity::class, $persistedOther);
      verify($persistedOther->getStatus())->same(ScheduledTaskEntity::STATUS_SCHEDULED);
    }
  }

  public function testItThrowsOnUnknownTypeListingValidTypes() {
    try {
      $this->runner->run('totally_bogus_type');
      $this->fail('Expected an InvalidArgumentException.');
    } catch (InvalidArgumentException $e) {
      verify($e->getMessage())->stringContainsString("Unknown task type 'totally_bogus_type'");
      verify($e->getMessage())->stringContainsString(UnsubscribeTokens::TASK_TYPE);
    }
  }

  public function testItReportsLimitReachedWhenTimeoutIsHit() {
    $task = $this->taskFactory->create(UnsubscribeTokens::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now());

    // timeout=0 means any elapsed time exceeds the limit, so the runner aborts before claiming anything.
    $result = $this->runner->run(UnsubscribeTokens::TASK_TYPE, null, 0);

    verify($result['limit_reached'])->true();
    verify($result['completed'])->equals(0);
    verify($result['message'])->stringContainsString('Execution limit');

    $this->entityManager->refresh($task);
    verify($task->getStatus())->same(ScheduledTaskEntity::STATUS_SCHEDULED);
  }

  public function testBulkRunHandsBackTasksThatDoNotComplete() {
    // A worker that completes nothing (partial work) hands every due task back to the site cron.
    $task = $this->taskFactory->create(UnsubscribeTokens::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now());

    $worker = $this->makePartialWorker(UnsubscribeTokens::TASK_TYPE);
    $runner = $this->makeTaskRunnerWithWorker($worker);

    $result = $runner->run(UnsubscribeTokens::TASK_TYPE);

    verify($result['completed'])->equals(0);
    // Handed-back tasks are not drained, so the command surfaces a warning rather than success.
    verify($result['backlog_drained'])->false();
    verify($result['message'])->stringContainsString('handed back');

    $this->entityManager->refresh($task);
    // Handed back to the site cron: scheduled and due now.
    verify($task->getStatus())->same(ScheduledTaskEntity::STATUS_SCHEDULED);
  }

  public function testBulkRunReportsLimitReachedWhenAWorkerHitsTheExecutionLimit() {
    // A worker that hits the execution limit yields rather than fails: the run stops with limit_reached
    // and the task is handed back, not reported as an error.
    $task = $this->taskFactory->create(UnsubscribeTokens::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now());

    $runner = $this->makeTaskRunnerWithWorker($this->makeLimitWorker(UnsubscribeTokens::TASK_TYPE));

    $result = $runner->run(UnsubscribeTokens::TASK_TYPE);

    verify($result['limit_reached'])->true();
    verify($result['completed'])->equals(0);
    verify($result['backlog_drained'])->false();

    $this->entityManager->refresh($task);
    verify($task->getStatus())->same(ScheduledTaskEntity::STATUS_SCHEDULED);
  }

  public function testItRunsTheMailingStatsNotificationWorker() {
    // No scheduled stats notifications exist, so the worker simply runs and completes nothing.
    $result = $this->runner->run(StatsNotificationsWorker::TASK_TYPE);

    verify($result['limit_reached'])->false();
    verify($result['completed'])->equals(0);
  }

  public function testItErrorsForSendingWhenMailerIsNotConfigured() {
    // Clear the mailer config and sender that a sibling test (DaemonRunnerTest::_before) writes to
    // the shared settings table, so the SendingQueue worker resolves an unconfigured mailer.
    $this->settings->delete(Mailer::MAILER_CONFIG_SETTING_NAME);
    $this->settings->delete('sender');
    $this->settings->resetCache();

    // The SendingQueue worker is a shared container service; a sibling test builds it with a configured
    // mailer, so the cached instance never re-evaluates these settings. Drive a fresh worker through a
    // stub factory so its constructor genuinely builds the mailer against the now-unconfigured settings.
    $runner = $this->makeTaskRunnerWithFreshQueueWorker();

    try {
      $runner->run(SendingQueueWorker::TASK_TYPE);
      $this->fail('Expected a RuntimeException for an unconfigured mailer.');
    } catch (RuntimeException $e) {
      verify($e->getMessage())->stringContainsString('Sending is not configured');
    }
  }

  public function testItRunsSchedulerThenSendingQueueForSending() {
    $this->configureMailer();

    // A scheduled sending task whose newsletter is a draft: the Scheduler runs and pauses it
    // (it is not active/scheduled/sending). The PAUSED status proves the Scheduler executed; the
    // SendingQueue then runs over runner tasks without error.
    $newsletter = (new NewsletterFactory())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withDraftStatus()
      ->create();
    $task = $this->taskFactory->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now());
    (new SendingQueueFactory())->create($task, $newsletter);

    $result = $this->runner->run(SendingQueueWorker::TASK_TYPE);

    verify($result['limit_reached'])->false();
    $this->entityManager->refresh($task);
    verify($task->getStatus())->same(ScheduledTaskEntity::STATUS_PAUSED);
  }

  private function configureMailer(): void {
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_PHPMAIL]);
    $this->settings->set('sender', ['name' => 'Sender', 'address' => 'sender@example.com']);
  }

  /**
   * A worker that records the task status it sees at process time, proving the bulk path claims rows
   * as 'cli' before processing. The return type is left off so the caller can read the public
   * recording properties on the anonymous class.
   */
  private function makeStatusRecordingWorker(string $type) {
    return new class($type) implements CronWorkerInterface {
      /** @var string */
      private $type;
      /** @var array<int, string|null> */
      public $seenStatuses = [];

      public function __construct(
        string $type
      ) {
        $this->type = $type;
      }

      public function getTaskType() {
        return $this->type;
      }

      public function scheduleAutomatically() {
        return false;
      }

      public function supportsMultipleInstances() {
        return true;
      }

      public function checkProcessingRequirements() {
        return true;
      }

      public function init() {
      }

      public function prepareTaskStrategy(ScheduledTaskEntity $task, $timer) {
        return true;
      }

      public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
        $this->seenStatuses[] = $task->getStatus();
        return true;
      }

      public function getNextRunDate() {
        return Carbon::now();
      }
    };
  }

  /**
   * A self-rescheduling batched worker: completing the task schedules one new due-now continuation of
   * the same type, mimicking SubscribersEngagementScore. Used to prove the bulk run does not chase
   * continuations created mid-run. The return type is left off so the caller can read the public
   * processCount on the anonymous class.
   */
  private function makeSelfReschedulingWorker(string $type) {
    return new class($type, $this->scheduledTasksRepository) implements CronWorkerInterface {
      /** @var string */
      private $type;
      /** @var ScheduledTasksRepository */
      private $repository;
      /** @var int */
      public $processCount = 0;

      public function __construct(
        string $type,
        ScheduledTasksRepository $repository
      ) {
        $this->type = $type;
        $this->repository = $repository;
      }

      public function getTaskType() {
        return $this->type;
      }

      public function scheduleAutomatically() {
        return false;
      }

      public function supportsMultipleInstances() {
        return true;
      }

      public function checkProcessingRequirements() {
        return true;
      }

      public function init() {
      }

      public function prepareTaskStrategy(ScheduledTaskEntity $task, $timer) {
        return true;
      }

      public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
        $this->processCount++;
        $continuation = new ScheduledTaskEntity();
        $continuation->setType($this->type);
        $continuation->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
        $continuation->setScheduledAt(Carbon::now());
        $this->repository->persist($continuation);
        $this->repository->flush();
        return true;
      }

      public function getNextRunDate() {
        return Carbon::now();
      }
    };
  }

  /**
   * A worker whose requirements are never met. The bulk run must not claim any task for it.
   */
  private function makeRequirementsNotMetWorker(string $type): CronWorkerInterface {
    return new class($type) implements CronWorkerInterface {
      /** @var string */
      private $type;

      public function __construct(
        string $type
      ) {
        $this->type = $type;
      }

      public function getTaskType() {
        return $this->type;
      }

      public function scheduleAutomatically() {
        return false;
      }

      public function supportsMultipleInstances() {
        return true;
      }

      public function checkProcessingRequirements() {
        return false;
      }

      public function init() {
      }

      public function prepareTaskStrategy(ScheduledTaskEntity $task, $timer) {
        return true;
      }

      public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
        return true;
      }

      public function getNextRunDate() {
        return Carbon::now();
      }
    };
  }

  /**
   * A worker that processes (requirements met, prepare true) but never completes, so every due task is
   * handed back to the site cron.
   */
  private function makePartialWorker(string $type): CronWorkerInterface {
    return new class($type) implements CronWorkerInterface {
      /** @var string */
      private $type;

      public function __construct(
        string $type
      ) {
        $this->type = $type;
      }

      public function getTaskType() {
        return $this->type;
      }

      public function scheduleAutomatically() {
        return false;
      }

      public function supportsMultipleInstances() {
        return true;
      }

      public function checkProcessingRequirements() {
        return true;
      }

      public function init() {
      }

      public function prepareTaskStrategy(ScheduledTaskEntity $task, $timer) {
        return true;
      }

      public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
        return false;
      }

      public function getNextRunDate() {
        return Carbon::now();
      }
    };
  }

  private function makeLimitWorker(string $type): CronWorkerInterface {
    return new class($type) implements CronWorkerInterface {
      /** @var string */
      private $type;

      public function __construct(
        string $type
      ) {
        $this->type = $type;
      }

      public function getTaskType() {
        return $this->type;
      }

      public function scheduleAutomatically() {
        return false;
      }

      public function supportsMultipleInstances() {
        return true;
      }

      public function checkProcessingRequirements() {
        return true;
      }

      public function init() {
      }

      public function prepareTaskStrategy(ScheduledTaskEntity $task, $timer) {
        return true;
      }

      public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
        throw new \Exception('execution limit reached', CronHelper::DAEMON_EXECUTION_LIMIT_REACHED);
      }

      public function getNextRunDate() {
        return Carbon::now();
      }
    };
  }

  private function makeTaskRunnerWithWorker(CronWorkerInterface $worker): TaskRunner {
    return new class(
      $this->diContainer->get(WorkerTypesCatalog::class),
      $this->diContainer->get(WorkersFactory::class),
      $this->diContainer->get(ExecutionLimitOverride::class),
      $this->diContainer->get(ScheduledTasksRepository::class),
      $this->diContainer->get(ScheduledTaskResolver::class),
      $this->diContainer->get(ClaimedTaskRunner::class),
      $worker
    ) extends TaskRunner {
      /** @var ?CronWorkerInterface */
      private $stubWorker;

      public function __construct(
        WorkerTypesCatalog $workerTypesCatalog,
        WorkersFactory $workersFactory,
        ExecutionLimitOverride $executionLimitOverride,
        ScheduledTasksRepository $scheduledTasksRepository,
        ScheduledTaskResolver $taskResolver,
        ClaimedTaskRunner $claimedTaskRunner,
        CronWorkerInterface $stubWorker
      ) {
        parent::__construct(
          $workerTypesCatalog,
          $workersFactory,
          $executionLimitOverride,
          $scheduledTasksRepository,
          $taskResolver,
          $claimedTaskRunner
        );
        $this->stubWorker = $stubWorker;
      }

      protected function resolveWorker(string $type): ?CronWorkerInterface {
        return $this->stubWorker;
      }
    };
  }

  /**
   * A TaskRunner whose WorkersFactory rebuilds the mailer on createQueueWorker(), bypassing the shared
   * container singletons (SendingQueue worker + MailerTask) that a sibling test already built with a
   * configured mailer. Building a fresh MailerTask runs the real mailer resolution against the
   * now-unconfigured settings, so it throws InvalidStateException exactly as a first-time build would.
   */
  private function makeTaskRunnerWithFreshQueueWorker(): TaskRunner {
    $freshWorkersFactory = new class($this->diContainer) extends WorkersFactory {
      /** @var ContainerWrapper */
      private $container;

      public function __construct(
        ContainerWrapper $container
      ) {
        parent::__construct($container);
        $this->container = $container;
      }

      public function createQueueWorker() {
        // Building MailerTask eagerly resolves the mailer and throws on an unconfigured site, which is
        // the failure runSending() catches. MailerFactory reads settings live, so a fresh MailerTask
        // reflects the current (unconfigured) state instead of the cached singleton.
        new MailerTask($this->container->get(MailerFactory::class));
        return $this->container->get(SendingQueueWorker::class);
      }
    };

    return new TaskRunner(
      $this->diContainer->get(WorkerTypesCatalog::class),
      $freshWorkersFactory,
      $this->diContainer->get(ExecutionLimitOverride::class),
      $this->diContainer->get(ScheduledTasksRepository::class),
      $this->diContainer->get(ScheduledTaskResolver::class),
      $this->diContainer->get(ClaimedTaskRunner::class)
    );
  }
}
