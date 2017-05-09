<?php

use Carbon\Carbon;
use Codeception\Util\Stub;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\SimpleWorker;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Setting;

require_once('SimpleWorkerMockImplementation.php');
use MailPoet\Cron\Workers\MockSimpleWorker;

class SimpleWorkerTest extends MailPoetTest {
  function _before() {
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
    } catch(\Exception $e) {
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
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('Maximum execution time has been reached.');
    }
  }

  function testItSchedulesTask() {
    expect(SendingQueue::where('type', MockSimpleWorker::TASK_TYPE)->findMany())->isEmpty();
    MockSimpleWorker::schedule();
    expect(SendingQueue::where('type', MockSimpleWorker::TASK_TYPE)->findMany())->notEmpty();
  }

  function testItDoesNotScheduleTaskTwice() {
    expect(count(SendingQueue::where('type', MockSimpleWorker::TASK_TYPE)->findMany()))->equals(0);
    MockSimpleWorker::schedule();
    expect(count(SendingQueue::where('type', MockSimpleWorker::TASK_TYPE)->findMany()))->equals(1);
    MockSimpleWorker::schedule();
    expect(count(SendingQueue::where('type', MockSimpleWorker::TASK_TYPE)->findMany()))->equals(1);
  }

  function testItCanGetScheduledQueues() {
    expect(MockSimpleWorker::getScheduledQueues())->isEmpty();
    $this->createScheduledQueue();
    expect(MockSimpleWorker::getScheduledQueues())->notEmpty();
  }

  function testItCanGetRunningQueues() {
    expect(MockSimpleWorker::getRunningQueues())->isEmpty();
    $this->createRunningQueue();
    expect(MockSimpleWorker::getRunningQueues())->notEmpty();
  }

  function testItCanGetAllDueQueues() {
    expect(MockSimpleWorker::getAllDueQueues())->isEmpty();

    // scheduled for now
    $this->createScheduledQueue();

    // running
    $this->createRunningQueue();

    // scheduled in the future (should not be retrieved)
    $queue = $this->createScheduledQueue();
    $queue->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'))->addDays(7);
    $queue->save();

    // completed (should not be retrieved)
    $queue = $this->createRunningQueue();
    $queue->status = SendingQueue::STATUS_COMPLETED;
    $queue->save();

    expect(count(MockSimpleWorker::getAllDueQueues()))->equals(2);
  }

  function testItCanGetFutureQueues() {
    expect(MockSimpleWorker::getFutureQueues())->isEmpty();
    $queue = $this->createScheduledQueue();
    $queue->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'))->addDays(7);
    $queue->save();
    expect(count(MockSimpleWorker::getFutureQueues()))->notEmpty();
  }

  function testItFailsToProcessWithoutQueues() {
    expect($this->worker->process())->false();
  }

  function testItFailsToProcessWithoutProcessingRequirementsMet() {
    $this->createScheduledQueue();
    $this->createRunningQueue();
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
      array('init' => Stub::once()),
      $this
    );
    $worker->process();
  }

  function testItProcesses() {
    $this->createScheduledQueue();
    $this->createRunningQueue();
    expect($this->worker->process())->true();
  }

  function testItPreparesQueue() {
    $queue = $this->createScheduledQueue();
    $this->worker->prepareQueue($queue);
    expect($queue->status)->null();
  }

  function testItProcessesQueue() {
    $queue = $this->createRunningQueue();
    $result = $this->worker->processQueue($queue);
    expect($queue->status)->equals(SendingQueue::STATUS_COMPLETED);
    expect($result)->equals(true);
  }

  function testItReturnsFalseIfInnerProcessingFunctionReturnsFalse() {
    $queue = $this->createRunningQueue();
    $worker = Stub::construct(
      $this->worker,
      array(),
      array('processQueueLogic' => false),
      $this
    );
    $result = $worker->processQueue($queue);
    expect($queue->status)->equals(null);
    expect($result)->equals(false);
  }

  function testItCanRescheduleTasks() {
    $queue = $this->createRunningQueue();
    $scheduled_at = $queue->scheduled_at;
    $this->worker->reschedule($queue, 10);
    expect($scheduled_at < $queue->scheduled_at)->true();
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

  private function createScheduledQueue() {
    $queue = SendingQueue::create();
    $queue->type = MockSimpleWorker::TASK_TYPE;
    $queue->status = SendingQueue::STATUS_SCHEDULED;
    $queue->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $queue->newsletter_id = 0;
    $queue->save();
    return $queue;
  }

  private function createRunningQueue() {
    $queue = SendingQueue::create();
    $queue->type = MockSimpleWorker::TASK_TYPE;
    $queue->status = null;
    $queue->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $queue->newsletter_id = 0;
    $queue->save();
    return $queue;
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Setting::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}