<?php
namespace MailPoet\Test\Cron\Workers;

use Carbon\Carbon;
use MailPoet\Models\ScheduledTask;

require_once('SingleInstanceSimpleWorkerMockImplementation.php');
use MailPoet\Cron\Workers\SingleInstanceSimpleWorkerMockImplementation as MockSimpleWorker;

class SingleInstanceSimpleWorkerTest extends \MailPoetTest {
  function _before() {
    $this->worker = $this->getMockBuilder(MockSimpleWorker::class)
      ->setMethods(['processTaskStrategy'])
      ->getMock();
  }

  function testItWillNotRunInMultipleInstances() {
    $this->worker->expects($this->once())
      ->method('processTaskStrategy')
      ->willReturn(true);
    $task = $this->createScheduledTask();
    expect(empty($task->getMeta()['in_progress']))->equals(true);
    expect($this->worker->processTask($task))->equals(true);
    $task->meta = ['in_progress' => true];
    expect($this->worker->processTask($task))->equals(false);
  }

  function testItWillResetTheInProgressFlagOnFail() {
    $task = $this->createScheduledTask();
    $this->worker->expects($this->once())
      ->method('processTaskStrategy')
      ->willThrowException(new \Exception('test error'));
    try {
      $this->worker->processTask($task);
      $this->fail('An exception should be thrown');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('test error');
      expect(empty($task->getMeta()['in_progress']))->equals(true);
    }
  }

  function testItWillRescheduleTaskIfItIsRunningForTooLong() {
    $this->worker->expects($this->once())
      ->method('processTaskStrategy')
      ->willReturn(true);
    $task = $this->createScheduledTask();
    $task = ScheduledTask::findOne($task->id); // make sure `updated_at` is set by the DB
    expect($this->worker->processTask($task))->equals(true);
    $scheduled_at = $task->scheduled_at;
    $task->updated_at = Carbon::createFromTimestamp(strtotime($task->updated_at))
      ->subMinutes(MockSimpleWorker::TASK_RUN_TIMEOUT + 1);
    expect($this->worker->processTask($task))->equals(false);
    $task = ScheduledTask::findOne($task->id);
    expect($scheduled_at < $task->scheduled_at)->true();
    expect($task->status)->equals(ScheduledTask::STATUS_SCHEDULED);
    expect(empty($task->getMeta()['in_progress']))->equals(true);
  }

  private function createScheduledTask() {
    $task = ScheduledTask::create();
    $task->type = MockSimpleWorker::TASK_TYPE;
    $task->status = null;
    $task->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $task->save();
    return $task;
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
  }
}
