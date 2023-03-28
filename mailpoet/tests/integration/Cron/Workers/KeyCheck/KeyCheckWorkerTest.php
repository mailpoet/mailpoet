<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers\KeyCheck;

use Codeception\Stub;
use MailPoet\Cron\CronWorkerScheduler;
use MailPoet\Cron\Workers\KeyCheck\KeyCheckWorkerMockImplementation as MockKeyCheckWorker;
use MailPoet\Services\Bridge;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

require_once('KeyCheckWorkerMockImplementation.php');

class KeyCheckWorkerTest extends \MailPoetTest {
  /** @var MockKeyCheckWorker */
  public $worker;

  /** @var ScheduledTaskFactory */
  private $scheduledTaskFactory;

  public function _before() {
    parent::_before();
    $this->scheduledTaskFactory = new ScheduledTaskFactory();
    $cronWorkerScheduler = $this->diContainer->get(CronWorkerScheduler::class);
    $this->worker = new MockKeyCheckWorker($cronWorkerScheduler);
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
    $cronWorkerSchedulerMock = $this->createMock(CronWorkerScheduler::class);
    $cronWorkerSchedulerMock->expects($this->once())->method('rescheduleProgressively');

    $worker = Stub::make(
      $this->worker,
      [
        'checkKey' => function () {
          throw new \Exception;
        },
        'cronWorkerScheduler' => $cronWorkerSchedulerMock,
      ],
      $this
    );

    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $task = $this->createRunningTask($currentTime);

    $result = $worker->processTaskStrategy($task, microtime(true));

    $this->assertFalse($result);
  }

  public function testItReschedulesCheckOnError() {
    $cronWorkerSchedulerMock = $this->createMock(CronWorkerScheduler::class);
    $cronWorkerSchedulerMock->expects($this->once())->method('rescheduleProgressively');

    $worker = Stub::make(
      $this->worker,
      [
        'checkKey' => ['code' => Bridge::CHECK_ERROR_UNAVAILABLE],
        'cronWorkerScheduler' => $cronWorkerSchedulerMock,
      ],
      $this
    );

    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $task = $this->createRunningTask($currentTime);

    $result = $worker->processTaskStrategy($task, microtime(true));

    $this->assertFalse($result);
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

  private function createRunningTask(Carbon $scheduledAt = null) {
    if (!$scheduledAt) {
      $scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    }

    return $this->scheduledTaskFactory->create(
      MockKeyCheckWorker::TASK_TYPE,
      null,
      $scheduledAt
    );
  }
}
