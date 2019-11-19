<?php

namespace MailPoet\Test\Cron\Workers;

use Carbon\Carbon;
use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\SimpleWorkerMockImplementation as MockSimpleWorker;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\ScheduledTask;
use MailPoet\Settings\SettingsRepository;

require_once('SimpleWorkerMockImplementation.php');

class SimpleWorkerTest extends \MailPoetTest {
  function _before() {
    parent::_before();
    $this->cron_helper = ContainerWrapper::getInstance()->get(CronHelper::class);
    $this->worker = new MockSimpleWorker();
  }

  function testItRequiresTaskTypeToConstruct() {
    $worker = Stub::make(
      'MailPoet\Cron\Workers\SimpleWorker',
      [],
      $this
    );
    try {
      $worker_class = get_class($worker);
      new $worker_class();
      $this->fail('SimpleWorker did not throw an exception');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Constant TASK_TYPE is not defined on subclass ' . $worker_class);
    }
  }

  function testItConstructs() {
    expect($this->worker->timer)->notEmpty();
  }

  function testItThrowsExceptionWhenExecutionLimitIsReached() {
    try {
      new MockSimpleWorker(
        microtime(true) - $this->cron_helper->getDaemonExecutionLimit()
      );
      self::fail('Maximum execution time limit exception was not thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Maximum execution time has been reached.');
    }
  }

  function testItSchedulesTask() {
    expect(ScheduledTask::where('type', MockSimpleWorker::TASK_TYPE)->findMany())->isEmpty();
    (new MockSimpleWorker())->schedule();
    expect(ScheduledTask::where('type', MockSimpleWorker::TASK_TYPE)->findMany())->notEmpty();
  }

  function testItDoesNotScheduleTaskTwice() {
    $worker = new MockSimpleWorker();
    expect(count(ScheduledTask::where('type', MockSimpleWorker::TASK_TYPE)->findMany()))->equals(0);
    $worker->schedule();
    expect(count(ScheduledTask::where('type', MockSimpleWorker::TASK_TYPE)->findMany()))->equals(1);
    $worker->schedule();
    expect(count(ScheduledTask::where('type', MockSimpleWorker::TASK_TYPE)->findMany()))->equals(1);
  }

  function testItCanGetScheduledTasks() {
    $worker = new MockSimpleWorker();
    expect($worker->getDueTasks())->isEmpty();
    $this->createScheduledTask();
    expect($worker->getDueTasks())->notEmpty();
  }

  function testItCanGetABatchOfScheduledTasks() {
    $worker = new MockSimpleWorker();
    for ($i = 0; $i < MockSimpleWorker::TASK_BATCH_SIZE + 5; $i += 1) {
      $this->createScheduledTask();
    }
    expect(count($worker->getDueTasks()))->equals(MockSimpleWorker::TASK_BATCH_SIZE);
  }

  function testItCanGetRunningTasks() {
    $worker = new MockSimpleWorker();
    expect($worker->getRunningTasks())->isEmpty();
    $this->createRunningTask();
    expect($worker->getRunningTasks())->notEmpty();
  }

  function testItCanGetBatchOfRunningTasks() {
    $worker = new MockSimpleWorker();
    for ($i = 0; $i < MockSimpleWorker::TASK_BATCH_SIZE + 5; $i += 1) {
      $this->createRunningTask();
    }
    expect(count($worker->getRunningTasks()))->equals(MockSimpleWorker::TASK_BATCH_SIZE);
  }

  function testItCanGetBatchOfCompletedTasks() {
    $worker = new MockSimpleWorker();
    for ($i = 0; $i < MockSimpleWorker::TASK_BATCH_SIZE + 5; $i += 1) {
      $this->createCompletedTask();
    }
    expect(count($worker->getCompletedTasks()))->equals(MockSimpleWorker::TASK_BATCH_SIZE);
  }

  function testItFailsToProcessWithoutTasks() {
    expect($this->worker->process())->false();
  }

  function testItFailsToProcessWithoutProcessingRequirementsMet() {
    $this->createScheduledTask();
    $this->createRunningTask();
    $worker = Stub::make(
      $this->worker,
      ['checkProcessingRequirements' => false],
      $this
    );
    expect($worker->process())->false();
  }

  function testItCanInitBeforeProcessing() {
    $worker = Stub::make(
      $this->worker,
      [
        'init' => Expected::once(),
        'schedule' => Expected::once(),
      ],
      $this
    );
    $worker->process();
  }

  function testItProcesses() {
    $this->createScheduledTask();
    $this->createRunningTask();
    expect($this->worker->process())->true();
  }

  function testItPreparesTask() {
    $task = $this->createScheduledTask();
    $this->worker->prepareTask($task);
    expect($task->status)->null();
  }

  function testItProcessesTask() {
    $task = $this->createRunningTask();
    $result = $this->worker->processTask($task);
    expect($task->status)->equals(ScheduledTask::STATUS_COMPLETED);
    expect($result)->equals(true);
  }

  function testItReturnsFalseIfInnerProcessingFunctionReturnsFalse() {
    $task = $this->createRunningTask();
    $worker = Stub::construct(
      $this->worker,
      [],
      ['processTaskStrategy' => false],
      $this
    );
    $result = $worker->processTask($task);
    expect($task->status)->equals(null);
    expect($result)->equals(false);
  }

  function testItCanRescheduleTasks() {
    $task = $this->createRunningTask();
    $scheduled_at = $task->scheduled_at;
    $this->worker->reschedule($task, 10);
    expect($scheduled_at < $task->scheduled_at)->true();
    expect($task->status)->equals(ScheduledTask::STATUS_SCHEDULED);
  }

  function testWillRescheduleATaskIfItFails() {
    $task = $this->createRunningTask();
    $worker = Stub::construct(
      $this->worker,
      [],
      [
        'processTaskStrategy' => function () {
          throw new \Exception('test error');
        },
      ],
      $this
    );
    $scheduled_at = $task->scheduled_at;
    try {
      $worker->process();
      $this->fail('An exception should be thrown');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('test error');
      $task = ScheduledTask::findOne($task->id);
      expect($scheduled_at < $task->scheduled_at)->true();
      expect($task->status)->equals(ScheduledTask::STATUS_SCHEDULED);
      expect($task->reschedule_count)->equals(1);
    }
  }

  function testWillNotRescheduleATaskOnCronTimeout() {
    $task = $this->createRunningTask();
    $worker = Stub::construct(
      $this->worker,
      [],
      [
        'processTaskStrategy' => function () {
          $this->cron_helper->enforceExecutionLimit(microtime(true) - CronHelper::DAEMON_EXECUTION_LIMIT - 1);
        },
      ],
      $this
    );
    $scheduled_at = $task->scheduled_at;
    try {
      $worker->process();
      $this->fail('An exception should be thrown');
    } catch (\Exception $e) {
      expect($e->getCode())->equals(CronHelper::DAEMON_EXECUTION_LIMIT_REACHED);
      $task = ScheduledTask::findOne($task->id);
      expect($scheduled_at)->equals($task->scheduled_at);
      expect($task->status)->equals(null);
      expect($task->reschedule_count)->equals(0);
    }
  }

  function testItWillNotRunInMultipleInstances() {
    $worker = $this->getMockBuilder(MockSimpleWorker::class)
      ->setMethods(['processTaskStrategy'])
      ->getMock();
    $worker->expects($this->once())
      ->method('processTaskStrategy')
      ->willReturn(true);
    $task = $this->createRunningTask();
    expect(empty($task->in_progress))->equals(true);
    expect($worker->processTask($task))->equals(true);
    $task->in_progress = true;
    expect($worker->processTask($task))->equals(false);
  }

  function testItWillResetTheInProgressFlagOnFail() {
    $worker = $this->getMockBuilder(MockSimpleWorker::class)
      ->setMethods(['processTaskStrategy'])
      ->getMock();
    $worker->expects($this->once())
      ->method('processTaskStrategy')
      ->willThrowException(new \Exception('test error'));
    $task = $this->createRunningTask();
    try {
      $worker->processTask($task);
      $this->fail('An exception should be thrown');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('test error');
      expect(empty($task->in_progress))->equals(true);
    }
  }

  function testItWillRescheduleTaskIfItIsRunningForTooLong() {
    $worker = $this->getMockBuilder(MockSimpleWorker::class)
      ->setMethods(['processTaskStrategy'])
      ->getMock();
    $worker->expects($this->once())
      ->method('processTaskStrategy')
      ->willReturn(true);
    $task = $this->createRunningTask();
    $task = ScheduledTask::findOne($task->id); // make sure `updated_at` is set by the DB
    expect($worker->processTask($task))->equals(true);
    $scheduled_at = $task->scheduled_at;
    $task->updated_at = Carbon::createFromTimestamp(strtotime($task->updated_at))
      ->subMinutes(MockSimpleWorker::TASK_RUN_TIMEOUT + 1);
    expect($worker->processTask($task))->equals(false);
    $task = ScheduledTask::findOne($task->id);
    expect($scheduled_at < $task->scheduled_at)->true();
    expect($task->status)->equals(ScheduledTask::STATUS_SCHEDULED);
    expect(empty($task->in_progress))->equals(true);
  }

  function testItCalculatesNextRunDateWithinNextWeekBoundaries() {
    $current_date = Carbon::createFromTimestamp(current_time('timestamp'));
    $next_run_date = (new MockSimpleWorker())->getNextRunDate();
    $difference = $next_run_date->diffInDays($current_date);
    // Subtract days left in the current week
    $difference -= (Carbon::DAYS_PER_WEEK - $current_date->format('N'));
    expect($difference)->lessOrEquals(7);
    expect($difference)->greaterOrEquals(0);
  }

  private function createScheduledTask() {
    $task = ScheduledTask::create();
    $task->type = MockSimpleWorker::TASK_TYPE;
    $task->status = ScheduledTask::STATUS_SCHEDULED;
    $task->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $task->save();
    return $task;
  }

  private function createRunningTask() {
    $task = ScheduledTask::create();
    $task->type = MockSimpleWorker::TASK_TYPE;
    $task->status = null;
    $task->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $task->save();
    return $task;
  }

  private function createCompletedTask() {
    $task = ScheduledTask::create();
    $task->type = MockSimpleWorker::TASK_TYPE;
    $task->status = ScheduledTask::STATUS_COMPLETED;
    $task->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $task->save();
    return $task;
  }

  function _after() {
    $this->di_container->get(SettingsRepository::class)->truncate();
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
  }
}
