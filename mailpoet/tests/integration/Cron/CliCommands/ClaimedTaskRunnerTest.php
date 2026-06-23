<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\CliCommands;

use MailPoet\Cron\CliCommands\ClaimedTaskRunner;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\CronWorkerInterface;
use MailPoet\Cron\Triggers\WordPress as WordPressTrigger;
use MailPoet\Cron\Workers\SubscribersCountCacheRecalculation;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use RuntimeException;

class ClaimedTaskRunnerTest extends \MailPoetTest {
  /** @var ClaimedTaskRunner */
  private $claimedTaskRunner;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var ScheduledTaskFactory */
  private $taskFactory;

  public function _before() {
    parent::_before();
    $this->claimedTaskRunner = $this->diContainer->get(ClaimedTaskRunner::class);
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->taskFactory = new ScheduledTaskFactory();
  }

  public function testClaimNewCreatesACliRowWithoutMetaOrInProgress() {
    $task = $this->claimedTaskRunner->claimNew(SubscribersCountCacheRecalculation::TASK_TYPE);

    // Re-read from the DB to assert the state was actually flushed.
    $this->entityManager->clear();
    $persisted = $this->scheduledTasksRepository->findOneById((int)$task->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $persisted);

    verify($persisted->getStatus())->same(ScheduledTaskEntity::STATUS_CLI);
    // No inProgress and no meta at claim time: the status alone hides the row from the daemon, and
    // a claim-time meta.cli would be clobbered by workers that overwrite meta mid-run.
    verify($persisted->getInProgress())->null();
    verify($persisted->getMeta())->null();
  }

  public function testClaimExistingTransitionsToCliPreservingMeta() {
    $existing = $this->taskFactory->create('typeExisting', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now());
    $existing->setMeta(['kept' => 'yes']);
    $this->entityManager->flush();

    verify($this->claimedTaskRunner->claimExisting($existing))->true();

    $this->entityManager->clear();
    $persisted = $this->scheduledTasksRepository->findOneById((int)$existing->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $persisted);
    verify($persisted->getStatus())->same(ScheduledTaskEntity::STATUS_CLI);
    // Existing meta is preserved by the claim.
    $meta = $persisted->getMeta();
    $this->assertIsArray($meta);
    verify($meta['kept'])->same('yes');
  }

  public function testClaimExistingIsAtomicAndLosesToAConcurrentClaim() {
    $task = $this->taskFactory->create('typeRace', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now());

    // First claim wins and flips the row to cli; a second claim of the now-cli row loses, so two CLI
    // processes that snapshotted the same scheduled row never both process it.
    verify($this->claimedTaskRunner->claimExisting($task))->true();
    verify($task->getStatus())->same(ScheduledTaskEntity::STATUS_CLI);
    verify($this->claimedTaskRunner->claimExisting($task))->false();
  }

  public function testRunCompletesAndMergesCliMetaWithWorkerWrittenMeta() {
    $task = $this->claimedTaskRunner->claimNew('typeComplete');
    // The worker writes its own meta during processing; the cli breadcrumb must merge with it, not
    // clobber it (and must survive the worker's wholesale meta write).
    $worker = $this->makeWorker('typeComplete', true, false, true, false, ['x' => 1]);

    $result = $this->claimedTaskRunner->run($worker, $task);

    verify($result['completed'])->true();

    $this->entityManager->clear();
    $persisted = $this->scheduledTasksRepository->findOneById((int)$task->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $persisted);
    verify($persisted->getStatus())->same(ScheduledTaskEntity::STATUS_COMPLETED);
    verify($persisted->getProcessedAt())->notNull();

    $meta = $persisted->getMeta();
    $this->assertIsArray($meta);
    // Worker-written key and the cli breadcrumb both present.
    $this->assertArrayHasKey('x', $meta);
    verify($meta['x'])->same(1);
    $this->assertArrayHasKey('cli', $meta);
    verify($meta['cli']['pid'])->same(getmypid());
    $this->assertIsString($meta['cli']['started_at']);
  }

  public function testRunHandsBackAndRethrowsWhenWorkerThrows() {
    $task = $this->claimedTaskRunner->claimNew('typeThrow');
    $worker = $this->makeWorker('typeThrow', true, true, false);

    try {
      $this->claimedTaskRunner->run($worker, $task);
      $this->fail('Expected a RuntimeException.');
    } catch (RuntimeException $e) {
      verify($e->getMessage())->stringContainsString('failed while running');
      verify($e->getMessage())->stringContainsString('handed back');
    }

    $this->entityManager->clear();
    $persisted = $this->scheduledTasksRepository->findOneById((int)$task->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $persisted);
    verify($persisted->getStatus())->same(ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->assertScheduledNow($persisted);
  }

  public function testRunHandsBackWhenRequirementsCheckThrows() {
    $task = $this->claimedTaskRunner->claimNew('typeReqThrow');
    // checkProcessingRequirements() itself throws, before any processing happens.
    $worker = $this->makeWorker('typeReqThrow', true, false, false, true);

    try {
      $this->claimedTaskRunner->run($worker, $task);
      $this->fail('Expected a RuntimeException.');
    } catch (RuntimeException $e) {
      verify($e->getMessage())->stringContainsString('failed while running');
    }

    $this->entityManager->clear();
    $persisted = $this->scheduledTasksRepository->findOneById((int)$task->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $persisted);
    verify($persisted->getStatus())->same(ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->assertScheduledNow($persisted);
  }

  public function testRunHandsBackWithoutProcessingWhenPrepareReturnsFalse() {
    $task = $this->claimedTaskRunner->claimNew('typePrepareFalse');
    // Mirrors Bounce: prepareTaskStrategy returns false (nothing to do), so the task is never processed.
    $worker = $this->makeWorker('typePrepareFalse', true, false, true, false, null, false);

    $result = $this->claimedTaskRunner->run($worker, $task);

    verify($result['completed'])->false();
    verify($result['message'])->stringContainsString('handed back');
    // processTaskStrategy must not have run when prepare returns false.
    verify($worker->processCalled)->false();

    $this->entityManager->clear();
    $persisted = $this->scheduledTasksRepository->findOneById((int)$task->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $persisted);
    // Handed back to the site cron: scheduled, due now, no cli breadcrumb left behind.
    verify($persisted->getStatus())->same(ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->assertScheduledNow($persisted);
    $meta = $persisted->getMeta();
    verify($meta === null || !isset($meta['cli']))->true();
  }

  public function testRunProcessesWhenPrepareReturnsTrue() {
    $task = $this->claimedTaskRunner->claimNew('typePrepareTrue');
    $worker = $this->makeWorker('typePrepareTrue', true, false, true, false, null, true);

    $result = $this->claimedTaskRunner->run($worker, $task);

    verify($result['completed'])->true();
    verify($worker->processCalled)->true();

    $this->entityManager->clear();
    $persisted = $this->scheduledTasksRepository->findOneById((int)$task->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $persisted);
    verify($persisted->getStatus())->same(ScheduledTaskEntity::STATUS_COMPLETED);
  }

  public function testRunRemovesTheClaimedTaskWhenRequirementsNotMet() {
    $task = $this->claimedTaskRunner->claimNew('typeNoReq');
    $taskId = (int)$task->getId();
    $worker = $this->makeWorker('typeNoReq', false, false, true);

    try {
      $this->claimedTaskRunner->run($worker, $task);
      $this->fail('Expected a RuntimeException.');
    } catch (RuntimeException $e) {
      verify($e->getMessage())->stringContainsString('Requirements');
    }

    $this->entityManager->clear();
    verify($this->scheduledTasksRepository->findOneById($taskId))->null();
  }

  public function testRunHandsBackGracefullyWhenWorkerHitsExecutionLimit() {
    $task = $this->claimedTaskRunner->claimNew('typeLimit');
    // requirements met, processes, but hits the execution limit (the worker yields rather than fails).
    $worker = $this->makeWorker('typeLimit', true, false, false, false, null, true, true);

    // No exception: a hit limit is a graceful yield, surfaced via limit_reached.
    $result = $this->claimedTaskRunner->run($worker, $task, 5);

    verify($result['completed'])->false();
    verify($result['limit_reached'])->true();
    verify($result['message'])->stringContainsString('execution limit');

    $this->entityManager->clear();
    $persisted = $this->scheduledTasksRepository->findOneById((int)$task->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $persisted);
    // Handed back to the site cron to continue: scheduled, due now, no cli breadcrumb.
    verify($persisted->getStatus())->same(ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->assertScheduledNow($persisted);
    $meta = $persisted->getMeta();
    verify($meta === null || !isset($meta['cli']))->true();
  }

  public function testACliRowIsInvisibleToDaemonDueAndRunningQueries() {
    // The two confirmed holes: a long CLI run must not be findable by the daemon's due/running
    // queries for its type, so the daemon can neither pick it up nor reschedule it.
    $type = 'typeHidden';
    $this->claimedTaskRunner->claimNew($type);

    verify($this->scheduledTasksRepository->findDueByType($type))->arrayCount(0);
    verify($this->scheduledTasksRepository->findRunningByType($type))->arrayCount(0);
  }

  public function testACliRowIsNotCountedAsPendingWorkByCheckExecutionRequirements() {
    // Mirrors the WordPressTest baseline: with cron disabled, no sending keys, and only a future
    // stats-report task, checkExecutionRequirements() is false. A DUE cli row of a simple-worker type
    // must keep it false (it is invisible), whereas the same row as 'scheduled' flips it to true —
    // proving the cli status, not the row's presence, is what hides it.
    $settings = SettingsController::getInstance();
    $settings->set('cron_trigger', ['method' => 'none']);
    $settings->set(Bridge::API_KEY_SETTING_NAME, null);
    $settings->set(Bridge::PREMIUM_KEY_SETTING_NAME, null);

    $future = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp', true) + 600);
    $this->taskFactory->create(\MailPoet\Cron\Workers\SubscribersStatsReport::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, $future);

    $trigger = $this->diContainer->get(WordPressTrigger::class);
    verify($trigger->checkExecutionRequirements())->false();

    $cliRow = $this->taskFactory->create(SubscribersCountCacheRecalculation::TASK_TYPE, ScheduledTaskEntity::STATUS_CLI, Carbon::now());
    verify($trigger->checkExecutionRequirements())->false();

    // Flip the very same row to scheduled: now it IS pending work and the daemon wakes up.
    $cliRow->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->entityManager->flush();
    verify($trigger->checkExecutionRequirements())->true();
  }

  private function assertScheduledNow(ScheduledTaskEntity $task): void {
    $scheduledAt = $task->getScheduledAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $scheduledAt);
    verify(abs(Carbon::now()->getTimestamp() - $scheduledAt->getTimestamp()))->lessThan(120);
  }

  /**
   * @param array<string, mixed>|null $metaToWrite Meta the worker writes wholesale during processing.
   *
   * The return type is left off so callers can read the public processCalled flag on the anonymous
   * class.
   */
  private function makeWorker(
    string $type,
    bool $requirementsMet,
    bool $throws,
    bool $completed,
    bool $requirementsThrows = false,
    ?array $metaToWrite = null,
    bool $prepareReturns = true,
    bool $throwsExecutionLimit = false
  ) {
    return new class($type, $requirementsMet, $throws, $completed, $requirementsThrows, $metaToWrite, $prepareReturns, $throwsExecutionLimit) implements CronWorkerInterface {
      /** @var string */
      private $type;
      /** @var bool */
      private $requirementsMet;
      /** @var bool */
      private $throws;
      /** @var bool */
      private $completed;
      /** @var bool */
      private $requirementsThrows;
      /** @var array<string, mixed>|null */
      private $metaToWrite;
      /** @var bool */
      private $prepareReturns;
      /** @var bool */
      private $throwsExecutionLimit;
      /** @var bool */
      public $processCalled = false;

      public function __construct(
        string $type,
        bool $requirementsMet,
        bool $throws,
        bool $completed,
        bool $requirementsThrows,
        ?array $metaToWrite,
        bool $prepareReturns,
        bool $throwsExecutionLimit
      ) {
        $this->type = $type;
        $this->requirementsMet = $requirementsMet;
        $this->throws = $throws;
        $this->completed = $completed;
        $this->requirementsThrows = $requirementsThrows;
        $this->metaToWrite = $metaToWrite;
        $this->prepareReturns = $prepareReturns;
        $this->throwsExecutionLimit = $throwsExecutionLimit;
      }

      public function getTaskType() {
        return $this->type;
      }

      public function scheduleAutomatically() {
        return false;
      }

      public function supportsMultipleInstances() {
        return false;
      }

      public function checkProcessingRequirements() {
        if ($this->requirementsThrows) {
          throw new \RuntimeException('requirements boom');
        }
        return $this->requirementsMet;
      }

      public function init() {
      }

      public function prepareTaskStrategy(ScheduledTaskEntity $task, $timer) {
        return $this->prepareReturns;
      }

      public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
        $this->processCalled = true;
        if ($this->metaToWrite !== null) {
          // Some workers overwrite meta wholesale mid-run.
          $task->setMeta($this->metaToWrite);
        }
        if ($this->throwsExecutionLimit) {
          // Mirrors CronHelper::enforceExecutionLimit when a worker exhausts its time budget.
          throw new \Exception('execution limit reached', CronHelper::DAEMON_EXECUTION_LIMIT_REACHED);
        }
        if ($this->throws) {
          throw new \RuntimeException('boom');
        }
        return $this->completed;
      }

      public function getNextRunDate() {
        return Carbon::now();
      }
    };
  }
}
