<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\CronWorkerRunner;
use MailPoet\Cron\CronWorkerScheduler;
use MailPoet\Cron\Workers\SimpleWorkerMockImplementation;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

require_once __DIR__ . '/Workers/SimpleWorkerMockImplementation.php';

class CronWorkerRunnerTest extends \MailPoetTest {
  /** @var CronWorkerRunner */
  private $cronWorkerRunner;

  /** @var CronHelper */
  private $cronHelper;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  public function _before() {
    $this->cronHelper = $this->diContainer->get(CronHelper::class);
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->cronWorkerRunner = new CronWorkerRunner(
      $this->cronHelper,
      $this->diContainer->get(CronWorkerScheduler::class),
      $this->diContainer->get(WPFunctions::class),
      $this->scheduledTasksRepository,
      $this->diContainer->get(LoggerFactory::class)
    );
  }

  public function testItCanInitBeforeProcessing() {
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'init' => Expected::once(),
      'scheduleAutomatically' => Expected::once(false),
    ]);
    $this->cronWorkerRunner->run($worker);
  }

  public function testItPreparesTaskAndProcessesItImmediately() {
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'prepareTaskStrategy' => Expected::once(true),
      'processTaskStrategy' => Expected::once(true),
    ]);

    $task = $this->createScheduledTask();
    $result = $this->cronWorkerRunner->run($worker);
    expect($result)->true();
    $scheduledTask = $this->scheduledTasksRepository->findOneById($task->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    expect($scheduledTask->getStatus())->same(ScheduledTaskEntity::STATUS_COMPLETED);
  }

  public function testItProcessesTask() {
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'prepareTaskStrategy' => Expected::never(),
      'processTaskStrategy' => Expected::once(true),
    ]);

    $task = $this->createRunningTask();
    $result = $this->cronWorkerRunner->run($worker);
    expect($result)->true();
    $scheduledTask = $this->scheduledTasksRepository->findOneById($task->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    expect($scheduledTask->getStatus())->same(ScheduledTaskEntity::STATUS_COMPLETED);
  }

  public function testItFailsToProcessWithoutTasks() {
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'scheduleAutomatically' => Expected::once(false),
      'prepareTaskStrategy' => Expected::never(),
      'processTaskStrategy' => Expected::never(),
    ]);

    $result = $this->cronWorkerRunner->run($worker);
    expect($result)->false();
  }

  public function testItFailsToProcessWithoutProcessingRequirementsMet() {
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'checkProcessingRequirements' => Expected::once(false),
      'prepareTaskStrategy' => Expected::never(),
      'processTaskStrategy' => Expected::never(),
    ]);

    $this->createScheduledTask();
    $this->createRunningTask();

    $result = $this->cronWorkerRunner->run($worker);
    expect($result)->false();
  }

  public function testItCanScheduleTaskAutomatically() {
    $inOneWeek = Carbon::now()->addWeek()->startOfDay();
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'scheduleAutomatically' => Expected::once(true),
      'getTaskType' => Expected::atLeastOnce(SimpleWorkerMockImplementation::TASK_TYPE),
      'getNextRunDate' => Expected::once($inOneWeek),
    ]);

    $result = $this->cronWorkerRunner->run($worker);
    expect($result)->false();
    $scheduledTask = $this->scheduledTasksRepository->findAll()[0];
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    expect($scheduledTask->getScheduledAt())->same($inOneWeek);
  }

  public function testItWillRescheduleTaskIfItIsRunningForTooLong() {
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'processTaskStrategy' => Expected::once(false),
    ]);
    $worker->__construct();

    $task = $this->createRunningTask();
    $task = $this->scheduledTasksRepository->findOneById($task->getId()); // make sure `updated_at` is set by the DB
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);

    $result = $this->cronWorkerRunner->run($worker);
    expect($result)->true();

    $scheduledAt = $task->getScheduledAt();
    $newUpdatedAt = $task->getUpdatedAt();
    $this->assertInstanceOf(Carbon::class, $newUpdatedAt);
    $newUpdatedAt->subMinutes(CronWorkerRunner::TASK_RUN_TIMEOUT + 1);
    $task->setUpdatedAt($newUpdatedAt);
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();

    $result = $this->cronWorkerRunner->run($worker);
    expect($result)->true();

    $task = $this->scheduledTasksRepository->findOneById($task->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    expect($task->getScheduledAt())->greaterThan($scheduledAt);
    expect($task->getStatus())->same(ScheduledTaskEntity::STATUS_SCHEDULED);
    expect($task->getInProgress())->isEmpty();

    // reset the state of the updatedAt field. this is needed to reset the state of TimestampListener::now otherwise it will impact other tests.
    // this code can be removed once https://mailpoet.atlassian.net/browse/MAILPOET-3870 is fixed.
    $updatedAt = $task->getUpdatedAt();
    $this->assertInstanceOf(Carbon::class, $updatedAt);
    $updatedAt->addMinutes(CronWorkerRunner::TASK_RUN_TIMEOUT + 1);
  }

  public function testItWillRescheduleATaskIfItFails() {
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'processTaskStrategy' => Expected::once(function () {
        throw new \Exception('test error');
      }),
    ]);

    $task = $this->createRunningTask();
    $scheduledAt = $task->getScheduledAt();
    try {
      $this->cronWorkerRunner->run($worker);
      $this->fail('An exception should be thrown');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('test error');
      $task = $this->scheduledTasksRepository->findOneById($task->getId());
      $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
      expect($task->getScheduledAt())->greaterThan($scheduledAt);
      expect($task->getStatus())->same(ScheduledTaskEntity::STATUS_SCHEDULED);
      expect($task->getRescheduleCount())->equals(1);
      expect($task->getInProgress())->isEmpty();
    }
  }

  public function testWillNotRescheduleATaskOnCronTimeout() {
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'processTaskStrategy' => Expected::once(function () {
        $this->cronHelper->enforceExecutionLimit(microtime(true) - CronHelper::DAEMON_EXECUTION_LIMIT - 1);
      }),
    ]);

    $task = $this->createRunningTask();
    $scheduledAt = $task->getScheduledAt();
    try {
      $this->cronWorkerRunner->run($worker);
      $this->fail('An exception should be thrown');
    } catch (\Exception $e) {
      expect($e->getCode())->same(CronHelper::DAEMON_EXECUTION_LIMIT_REACHED);
      $task = $this->scheduledTasksRepository->findOneById($task->getId());
      $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
      expect($scheduledAt)->equals($task->getScheduledAt());
      expect($task->getStatus())->null();
      expect($task->getRescheduleCount())->equals(0);
    }
  }

  public function testItWillNotRunInMultipleInstances() {
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'supportsMultipleInstances' => Expected::once(false),
      'processTaskStrategy' => Expected::never(),
    ]);

    $task = $this->createRunningTask();
    $task->setInProgress(true);

    $this->cronWorkerRunner->run($worker);
  }

  public function testItThrowsExceptionWhenExecutionLimitIsReached() {
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'processTaskStrategy' => Expected::never(),
    ]);

    $cronWorkerRunner = Stub::copy($this->cronWorkerRunner, [
      'timer' => microtime(true) - $this->diContainer->get(CronHelper::class)->getDaemonExecutionLimit(),
    ]);
    try {
      $cronWorkerRunner->run($worker);
      self::fail('Maximum execution time limit exception was not thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->startsWith('The maximum execution time');
    }
  }

  private function createScheduledTask(): ScheduledTaskEntity {
    return $this->createTask(ScheduledTaskEntity::STATUS_SCHEDULED);
  }

  private function createRunningTask(): ScheduledTaskEntity {
    return $this->createTask(null);
  }

  private function createTask($status): ScheduledTaskEntity {
    $factory = new ScheduledTaskFactory();

    return $factory->create(
      SimpleWorkerMockImplementation::TASK_TYPE,
      $status,
      Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))
    );
  }
}
