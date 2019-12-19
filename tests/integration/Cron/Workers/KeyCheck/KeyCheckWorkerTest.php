<?php

namespace MailPoet\Test\Cron\Workers\KeyCheck;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Cron\Workers\KeyCheck\KeyCheckWorkerMockImplementation as MockKeyCheckWorker;
use MailPoet\Models\ScheduledTask;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsRepository;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

require_once('KeyCheckWorkerMockImplementation.php');

class KeyCheckWorkerTest extends \MailPoetTest {
  public $worker;
  public function _before() {
    parent::_before();
    $this->worker = new MockKeyCheckWorker();
  }

  public function testItCanInitializeBridgeAPI() {
    $this->worker->init();
    expect($this->worker->bridge instanceof Bridge)->true();
  }

  public function testItReturnsTrueOnSuccessfulKeyCheck() {
    $task = $this->createRunningTask();
    $result = $this->worker->processTaskStrategy($task, microtime(true));
    expect($result)->true();
  }

  public function testItReschedulesCheckOnException() {
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
    $result = $worker->processTaskStrategy($task, microtime(true));
    expect($result)->false();
  }

  public function testItReschedulesCheckOnError() {
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
    $result = $worker->processTaskStrategy($task, microtime(true));
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

  public function _after() {
    $this->di_container->get(SettingsRepository::class)->truncate();
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
  }
}
