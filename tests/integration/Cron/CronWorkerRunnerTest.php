<?php

namespace MailPoet\Test\Cron;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\CronWorkerRunner;
use MailPoet\Cron\Workers\SimpleWorkerMockImplementation;
use MailPoet\Models\ScheduledTask;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

require_once __DIR__ . '/Workers/SimpleWorkerMockImplementation.php';

class CronWorkerRunnerTest extends \MailPoetTest {
  /** @var CronWorkerRunner */
  private $cronWorkerRunner;

  /** @var CronHelper */
  private $cronHelper;

  public function _before() {
    $this->cronWorkerRunner = $this->diContainer->get(CronWorkerRunner::class);
    $this->cronHelper = $this->diContainer->get(CronHelper::class);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
  }

  public function testItCanInitBeforeProcessing() {
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'init' => Expected::once(),
      'scheduleAutomatically' => Expected::once(false),
    ]);
    $this->cronWorkerRunner->run($worker);
  }

  public function testItPreparesTask() {
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'prepareTaskStrategy' => Expected::once(true),
      'processTaskStrategy' => Expected::never(),
    ]);

    $task = $this->createScheduledTask();
    $result = $this->cronWorkerRunner->run($worker);
    expect($result)->true();
    $scheduledTask = ScheduledTask::findOne($task->id);
    assert($scheduledTask instanceof ScheduledTask);
    expect($scheduledTask->status)->null();
  }

  public function testItProcessesTask() {
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'prepareTaskStrategy' => Expected::never(),
      'processTaskStrategy' => Expected::once(true),
    ]);

    $task = $this->createRunningTask();
    $result = $this->cronWorkerRunner->run($worker);
    expect($result)->true();
    $scheduledTask = ScheduledTask::findOne($task->id);
    assert($scheduledTask instanceof ScheduledTask);
    expect($scheduledTask->status)->same(ScheduledTask::STATUS_COMPLETED);
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
    $scheduledTask = ScheduledTask::findOne();
    assert($scheduledTask instanceof ScheduledTask);
    expect($scheduledTask->scheduledAt)->same($inOneWeek->format('Y-m-d H:i:s'));
  }

  public function testItWillRescheduleTaskIfItIsRunningForTooLong() {
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'processTaskStrategy' => Expected::once(false),
    ]);
    $worker->__construct();

    $task = $this->createRunningTask();
    $task = ScheduledTask::findOne($task->id); // make sure `updated_at` is set by the DB
    assert($task instanceof ScheduledTask);

    $result = $this->cronWorkerRunner->run($worker);
    expect($result)->true();

    $scheduledAt = $task->scheduledAt;
    $task->updatedAt = Carbon::createFromTimestamp((int)strtotime((string)$task->updatedAt))
      ->subMinutes(CronWorkerRunner::TASK_RUN_TIMEOUT + 1);
    $task->save();

    $result = $this->cronWorkerRunner->run($worker);
    expect($result)->true();

    $task = ScheduledTask::findOne($task->id);
    assert($task instanceof ScheduledTask);
    expect($task->scheduledAt)->greaterThan($scheduledAt);
    expect($task->status)->same(ScheduledTask::STATUS_SCHEDULED);
    expect($task->inProgress)->isEmpty();
  }

  public function testItWillRescheduleATaskIfItFails() {
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'processTaskStrategy' => Expected::once(function () {
        throw new \Exception('test error');
      }),
    ]);

    $task = $this->createRunningTask();
    $scheduledAt = $task->scheduledAt;
    try {
      $this->cronWorkerRunner->run($worker);
      $this->fail('An exception should be thrown');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('test error');
      $task = ScheduledTask::findOne($task->id);
      assert($task instanceof ScheduledTask);
      expect($task->scheduledAt)->greaterThan($scheduledAt);
      expect($task->status)->same(ScheduledTask::STATUS_SCHEDULED);
      expect($task->rescheduleCount)->equals(1);
      expect($task->inProgress)->isEmpty();
    }
  }

  public function testWillNotRescheduleATaskOnCronTimeout() {
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'processTaskStrategy' => Expected::once(function () {
        $this->cronHelper->enforceExecutionLimit(microtime(true) - CronHelper::DAEMON_EXECUTION_LIMIT - 1);
      }),
    ]);

    $task = $this->createRunningTask();
    $scheduledAt = $task->scheduledAt;
    try {
      $this->cronWorkerRunner->run($worker);
      $this->fail('An exception should be thrown');
    } catch (\Exception $e) {
      expect($e->getCode())->same(CronHelper::DAEMON_EXECUTION_LIMIT_REACHED);
      $task = ScheduledTask::findOne($task->id);
      assert($task instanceof ScheduledTask);
      expect($scheduledAt)->equals($task->scheduledAt);
      expect($task->status)->null();
      expect($task->rescheduleCount)->equals(0);
    }
  }

  public function testItWillNotRunInMultipleInstances() {
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'supportsMultipleInstances' => Expected::once(false),
      'processTaskStrategy' => Expected::never(),
    ]);

    $task = $this->createRunningTask();
    $task->inProgress = true;
    $task->save();

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
      expect($e->getMessage())->same('Maximum execution time has been reached.');
    }
  }

  private function createScheduledTask() {
    $task = ScheduledTask::create();
    $task->type = SimpleWorkerMockImplementation::TASK_TYPE;
    $task->status = ScheduledTask::STATUS_SCHEDULED;
    $task->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $task->save();
    return $task;
  }

  private function createRunningTask() {
    $task = ScheduledTask::create();
    $task->type = SimpleWorkerMockImplementation::TASK_TYPE;
    $task->status = null;
    $task->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $task->save();
    return $task;
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
  }
}
