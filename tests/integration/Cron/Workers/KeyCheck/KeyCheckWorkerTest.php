<?php

namespace MailPoet\Test\Cron\Workers\KeyCheck;

use Carbon\Carbon;
use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Cron\Workers\KeyCheck\KeyCheckWorkerMockImplementation as MockKeyCheckWorker;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Setting;
use MailPoet\Services\Bridge;

require_once('KeyCheckWorkerMockImplementation.php');

class KeyCheckWorkerTest extends \MailPoetTest {
  function _before() {
    parent::_before();
    $this->worker = new MockKeyCheckWorker();
  }

  function testItCanInitializeBridgeAPI() {
    $this->worker->init();
    expect($this->worker->bridge instanceof Bridge)->true();
  }

  function testItReturnsTrueOnSuccessfulKeyCheck() {
    $task = $this->createRunningTask();
    $result = $this->worker->processTaskStrategy($task);
    expect($result)->true();
  }

  function testItReschedulesCheckOnException() {
    $worker = Stub::make(
      $this->worker,
      [
        'checkKey' => function () {
          throw new \Exception;
        },
      ],
      $this
    );
    $task = Stub::make(
      ScheduledTask::class,
      ['rescheduleProgressively' => Expected::once()],
      $this
    );
    $result = $worker->processTaskStrategy($task);
    expect($result)->false();
  }

  function testItReschedulesCheckOnError() {
    $worker = Stub::make(
      $this->worker,
      [
        'checkKey' => ['code' => Bridge::CHECK_ERROR_UNAVAILABLE],
      ],
      $this
    );
    $task = Stub::make(
      ScheduledTask::class,
      ['rescheduleProgressively' => Expected::once()],
      $this
    );
    $result = $worker->processTaskStrategy($task);
    expect($result)->false();
  }

  private function createRunningTask() {
    $task = ScheduledTask::create();
    $task->type = MockKeyCheckWorker::TASK_TYPE;
    $task->status = null;
    $task->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $task->save();
    return $task;
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Setting::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
  }
}
