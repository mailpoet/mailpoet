<?php
namespace MailPoet\Test\Cron\Workers;

use Carbon\Carbon;
use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Cron\CronHelper;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Setting;

require_once('SimpleWorkerMockImplementation.php');
use MailPoet\Cron\Workers\SimpleWorkerMockImplementation as MockSimpleWorker;

class SimpleWorkerTest extends \MailPoetTest {
  function _before() {
    parent::_before();
    $this->worker = new MockSimpleWorker();
  }

  function testItRequiresTaskTypeToConstruct() {
    $worker = Stub::make(
      'MailPoet\Cron\Workers\SimpleWorker',
      array(),
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
      $worker = new MockSimpleWorker(
        microtime(true) - CronHelper::DAEMON_EXECUTION_LIMIT
      );
      self::fail('Maximum execution time limit exception was not thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Maximum execution time has been reached.');
    }
  }

  function testItSchedulesTask() {
    expect(ScheduledTask::where('type', MockSimpleWorker::TASK_TYPE)->findMany())->isEmpty();
    MockSimpleWorker::schedule();
    expect(ScheduledTask::where('type', MockSimpleWorker::TASK_TYPE)->findMany())->notEmpty();
  }

  function testItDoesNotScheduleTaskTwice() {
    expect(count(ScheduledTask::where('type', MockSimpleWorker::TASK_TYPE)->findMany()))->equals(0);
    MockSimpleWorker::schedule();
    expect(count(ScheduledTask::where('type', MockSimpleWorker::TASK_TYPE)->findMany()))->equals(1);
    MockSimpleWorker::schedule();
    expect(count(ScheduledTask::where('type', MockSimpleWorker::TASK_TYPE)->findMany()))->equals(1);
  }

  function testItCanGetScheduledTasks() {
    expect(MockSimpleWorker::getScheduledTasks())->isEmpty();
    $this->createScheduledTask();
    expect(MockSimpleWorker::getScheduledTasks())->notEmpty();
  }

  function testItCanGetABatchOfScheduledTasks() {
    for ($i = 0; $i < MockSimpleWorker::TASK_BATCH_SIZE + 5; $i += 1) {
      $this->createScheduledTask();
    }
    expect(count(MockSimpleWorker::getScheduledTasks()))->equals(MockSimpleWorker::TASK_BATCH_SIZE);
  }

  function testItCanGetRunningTasks() {
    expect(MockSimpleWorker::getRunningTasks())->isEmpty();
    $this->createRunningTask();
    expect(MockSimpleWorker::getRunningTasks())->notEmpty();
  }

  function testItCanGetBatchOfRunningTasks() {
    for ($i = 0; $i < MockSimpleWorker::TASK_BATCH_SIZE + 5; $i += 1) {
      $this->createRunningTask();
    }
    expect(count(MockSimpleWorker::getRunningTasks()))->equals(MockSimpleWorker::TASK_BATCH_SIZE);
  }

  function testItCanGetBatchOfCompletedTasks() {
    for ($i = 0; $i < MockSimpleWorker::TASK_BATCH_SIZE + 5; $i += 1) {
      $this->createCompletedTask();
    }
    expect(count(MockSimpleWorker::getCompletedTasks()))->equals(MockSimpleWorker::TASK_BATCH_SIZE);
  }

  function testItCanGetDueTasks() {
    expect(MockSimpleWorker::getDueTasks())->isEmpty();

    // scheduled for now
    $this->createScheduledTask();

    // running
    $this->createRunningTask();

    // scheduled in the future (should not be retrieved)
    $task = $this->createScheduledTask();
    $task->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'))->addDays(7);
    $task->save();

    // completed (should not be retrieved)
    $task = $this->createRunningTask();
    $task->status = ScheduledTask::STATUS_COMPLETED;
    $task->save();

    expect(count(MockSimpleWorker::getDueTasks()))->equals(2);
  }

  function testItFailsToProcessWithoutTasks() {
    expect($this->worker->process())->false();
  }

  function testItFailsToProcessWithoutProcessingRequirementsMet() {
    $this->createScheduledTask();
    $this->createRunningTask();
    $worker = Stub::make(
      $this->worker,
      array('checkProcessingRequirements' => false),
      $this
    );
    expect($worker->process())->false();
  }

  function testItCanInitBeforeProcessing() {
    $worker = Stub::make(
      $this->worker,
      array('init' => Expected::once()),
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
      array(),
      array('processTaskStrategy' => false),
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

  function testItCalculatesNextRunDateWithinNextWeekBoundaries() {
    $current_date = Carbon::createFromTimestamp(current_time('timestamp'));
    $next_run_date = MockSimpleWorker::getNextRunDate();
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
    \ORM::raw_execute('TRUNCATE ' . Setting::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
  }
}
