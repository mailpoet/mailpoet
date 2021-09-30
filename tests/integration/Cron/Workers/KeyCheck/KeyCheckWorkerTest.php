<?php

namespace MailPoet\Test\Cron\Workers\KeyCheck;

use Codeception\Stub;
use MailPoet\Cron\Workers\KeyCheck\KeyCheckWorkerMockImplementation as MockKeyCheckWorker;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsRepository;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

require_once('KeyCheckWorkerMockImplementation.php');

class KeyCheckWorkerTest extends \MailPoetTest {
  public $worker;

  /** @var ScheduledTaskFactory */
  private $scheduledTaskFactory;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  public function _before() {
    parent::_before();
    $this->scheduledTaskFactory = new ScheduledTaskFactory();
    $this->worker = new MockKeyCheckWorker();
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
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
    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $task = $this->createRunningTask($currentTime);
    $result = $worker->processTaskStrategy($task, microtime(true));

    // need to clear Doctrine cache and get the entity again while ScheduledTask::rescheduleProgressively() is not migrated to Doctrine
    $this->entityManager->clear();
    $task = $this->scheduledTasksRepository->findOneById($task->getId());

    assert($task instanceof ScheduledTaskEntity);
    assert($task->getScheduledAt() instanceof \DateTimeInterface);
    $newScheduledAtTime = $currentTime->addMinutes(5)->format('Y-m-d H:i:s');
    $scheduledAt = $task->getScheduledAt()->format('Y-m-d H:i:s');
    $this->assertFalse($result);
    $this->assertSame($newScheduledAtTime, $scheduledAt);
    $this->assertSame(1, $task->getRescheduleCount());
  }

  public function testItReschedulesCheckOnError() {
    $worker = Stub::make(
      $this->worker,
      [
        'checkKey' => ['code' => Bridge::CHECK_ERROR_UNAVAILABLE],
      ],
      $this
    );

    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $task = $this->createRunningTask($currentTime);

    $result = $worker->processTaskStrategy($task, microtime(true));

    // need to clear Doctrine cache and get the entity again while ScheduledTask::rescheduleProgressively() is not migrated to Doctrine
    $this->entityManager->clear();
    $task = $this->scheduledTasksRepository->findOneById($task->getId());

    assert($task instanceof ScheduledTaskEntity);
    assert($task->getScheduledAt() instanceof \DateTimeInterface);
    $newScheduledAtTime = $currentTime->addMinutes(5)->format('Y-m-d H:i:s');
    $scheduledAt = $task->getScheduledAt()->format('Y-m-d H:i:s');
    $this->assertFalse($result);
    $this->assertSame($newScheduledAtTime, $scheduledAt);
    $this->assertSame(1, $task->getRescheduleCount());
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

  public function _after() {
    $this->diContainer->get(SettingsRepository::class)->truncate();
    $this->truncateEntity(ScheduledTaskEntity::class);
  }
}
