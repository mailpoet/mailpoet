<?php

namespace MailPoet\Test\Cron\Workers\KeyCheck;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Cron\Workers\KeyCheck\KeyCheckWorkerMockImplementation as MockKeyCheckWorker;
use MailPoet\Models\ScheduledTask;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsRepository;
use MailPoet\WP\Functions as WPFunctions;
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

  public function testItNextRunIsNextDay(): void {
    $dateTime = Carbon::now();
    $wp = Stub::make(new WPFunctions, [
      'currentTime' => function($format) use ($dateTime) {
        return $dateTime->getTimestamp();
      },
    ]);

    $worker = Stub::make(
      $this->worker,
      [
        'checkKey' => ['code' => Bridge::CHECK_ERROR_UNAVAILABLE],
        'wp' => $wp,
      ],
      $this
    );

    /** @var Carbon $nextRunDate */
    $nextRunDate = $worker->getNextRunDate();
    $secondsToMidnight = $dateTime->diffInSeconds($dateTime->copy()->startOfDay()->addDay());

    // next run should be planned in 6 hours after midnight
    expect($nextRunDate->diffInSeconds($dateTime))->lessOrEquals(21600 + $secondsToMidnight);
  }

  private function createRunningTask() {
    $task = ScheduledTask::create();
    $task->type = MockKeyCheckWorker::TASK_TYPE;
    $task->status = null;
    $task->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $task->save();
    return $task;
  }

  public function _after() {
    $this->diContainer->get(SettingsRepository::class)->truncate();
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
  }
}
