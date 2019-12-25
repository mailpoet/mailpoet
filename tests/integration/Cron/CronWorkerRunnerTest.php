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
  private $cron_worker_runner;

  /** @var CronHelper */
  private $cron_helper;

  public function _before() {
    $this->cron_worker_runner = $this->di_container->get(CronWorkerRunner::class);
    $this->cron_helper = $this->di_container->get(CronHelper::class);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
  }

  public function testItCanInitBeforeProcessing() {
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'init' => Expected::once(),
      'scheduleAutomatically' => Expected::once(false),
    ]);
    $this->cron_worker_runner->run($worker);
  }

  public function testItPreparesTask() {
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'prepareTaskStrategy' => Expected::once(true),
      'processTaskStrategy' => Expected::never(),
    ]);

    $task = $this->createScheduledTask();
    $result = $this->cron_worker_runner->run($worker);
    expect($result)->true();
    expect(ScheduledTask::findOne($task->id)->status)->null();
  }

  public function testItProcessesTask() {
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'prepareTaskStrategy' => Expected::never(),
      'processTaskStrategy' => Expected::once(true),
    ]);

    $task = $this->createRunningTask();
    $result = $this->cron_worker_runner->run($worker);
    expect($result)->true();
    expect(ScheduledTask::findOne($task->id)->status)->same(ScheduledTask::STATUS_COMPLETED);
  }

  public function testItFailsToProcessWithoutTasks() {
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'scheduleAutomatically' => Expected::once(false),
      'prepareTaskStrategy' => Expected::never(),
      'processTaskStrategy' => Expected::never(),
    ]);

    $result = $this->cron_worker_runner->run($worker);
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

    $result = $this->cron_worker_runner->run($worker);
    expect($result)->false();
  }

  public function testItCanScheduleTaskAutomatically() {
    $in_one_week = Carbon::now()->addWeek()->startOfDay();
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'scheduleAutomatically' => Expected::once(true),
      'getTaskType' => Expected::atLeastOnce(SimpleWorkerMockImplementation::TASK_TYPE),
      'getNextRunDate' => Expected::once($in_one_week),
    ]);

    $result = $this->cron_worker_runner->run($worker);
    expect($result)->false();
    expect(ScheduledTask::findOne()->scheduled_at)->same($in_one_week->format('Y-m-d H:i:s'));
  }

  public function testItWillRescheduleTaskIfItIsRunningForTooLong() {
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'processTaskStrategy' => Expected::once(false),
    ]);
    $worker->__construct();

    $task = $this->createRunningTask();
    $task = ScheduledTask::findOne($task->id); // make sure `updated_at` is set by the DB

    $result = $this->cron_worker_runner->run($worker);
    expect($result)->true();

    $scheduled_at = $task->scheduled_at;
    $task->updated_at = Carbon::createFromTimestamp((int)strtotime($task->updated_at))
      ->subMinutes(CronWorkerRunner::TASK_RUN_TIMEOUT + 1);
    $task->save();

    $result = $this->cron_worker_runner->run($worker);
    expect($result)->true();

    $task = ScheduledTask::findOne($task->id);
    expect($task->scheduled_at)->greaterThan($scheduled_at);
    expect($task->status)->same(ScheduledTask::STATUS_SCHEDULED);
    expect($task->in_progress)->isEmpty();
  }

  public function testItWillRescheduleATaskIfItFails() {
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'processTaskStrategy' => Expected::once(function () {
        throw new \Exception('test error');
      }),
    ]);

    $task = $this->createRunningTask();
    $scheduled_at = $task->scheduled_at;
    try {
      $this->cron_worker_runner->run($worker);
      $this->fail('An exception should be thrown');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('test error');
      $task = ScheduledTask::findOne($task->id);
      expect($task->scheduled_at)->greaterThan($scheduled_at);
      expect($task->status)->same(ScheduledTask::STATUS_SCHEDULED);
      expect($task->reschedule_count)->equals(1);
      expect($task->in_progress)->isEmpty();
    }
  }

  public function testWillNotRescheduleATaskOnCronTimeout() {
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'processTaskStrategy' => Expected::once(function () {
        $this->cron_helper->enforceExecutionLimit(microtime(true) - CronHelper::DAEMON_EXECUTION_LIMIT - 1);
      }),
    ]);

    $task = $this->createRunningTask();
    $scheduled_at = $task->scheduled_at;
    try {
      $this->cron_worker_runner->run($worker);
      $this->fail('An exception should be thrown');
    } catch (\Exception $e) {
      expect($e->getCode())->same(CronHelper::DAEMON_EXECUTION_LIMIT_REACHED);
      $task = ScheduledTask::findOne($task->id);
      expect($scheduled_at)->equals($task->scheduled_at);
      expect($task->status)->null();
      expect($task->reschedule_count)->equals(0);
    }
  }

  public function testItWillNotRunInMultipleInstances() {
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'supportsMultipleInstances' => Expected::once(false),
      'processTaskStrategy' => Expected::never(),
    ]);

    $task = $this->createRunningTask();
    $task->in_progress = true;
    $task->save();

    $this->cron_worker_runner->run($worker);
  }

  public function testItThrowsExceptionWhenExecutionLimitIsReached() {
    $worker = $this->make(SimpleWorkerMockImplementation::class, [
      'processTaskStrategy' => Expected::never(),
    ]);

    $cron_worker_runner = Stub::copy($this->cron_worker_runner, [
      'timer' => microtime(true) - $this->di_container->get(CronHelper::class)->getDaemonExecutionLimit(),
    ]);
    try {
      $cron_worker_runner->run($worker);
      self::fail('Maximum execution time limit exception was not thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->same('Maximum execution time has been reached.');
    }
  }

  private function createScheduledTask() {
    $task = ScheduledTask::create();
    $task->type = SimpleWorkerMockImplementation::TASK_TYPE;
    $task->status = ScheduledTask::STATUS_SCHEDULED;
    $task->scheduled_at = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $task->save();
    return $task;
  }

  private function createRunningTask() {
    $task = ScheduledTask::create();
    $task->type = SimpleWorkerMockImplementation::TASK_TYPE;
    $task->status = null;
    $task->scheduled_at = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $task->save();
    return $task;
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
  }
}
