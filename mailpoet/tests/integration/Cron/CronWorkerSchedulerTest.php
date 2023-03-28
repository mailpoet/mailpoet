<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron;

use MailPoet\Cron\CronWorkerScheduler;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoetVendor\Carbon\Carbon;

require_once __DIR__ . '/Workers/SimpleWorkerMockImplementation.php';

class CronWorkerSchedulerTest extends \MailPoetTest {
  /** @var CronWorkerScheduler */
  private $cronWorkerScheduler;

  /** @var ScheduledTaskFactory */
  private $scheduledTaskFactory;

  public function _before() {
    $this->cronWorkerScheduler = $this->diContainer->get(CronWorkerScheduler::class);
    $this->scheduledTaskFactory = new ScheduledTaskFactory();
  }

  public function testItSchedulesTask() {
    $nextRunDate = Carbon::now()->addWeek();
    $this->cronWorkerScheduler->schedule('test', $nextRunDate);
    $tasks = $this->entityManager->getRepository(ScheduledTaskEntity::class)->findAll();
    expect($tasks)->count(1);
    expect($tasks[0]->getType())->same('test');
    expect($tasks[0]->getStatus())->same(ScheduledTaskEntity::STATUS_SCHEDULED);
    expect($tasks[0]->getScheduledAt())->same($nextRunDate);
  }

  public function testItDoesNotScheduleTaskTwice() {
    $nextRunDate = Carbon::now()->addWeek();
    $task = $this->cronWorkerScheduler->schedule('test', $nextRunDate);
    expect($this->entityManager->getRepository(ScheduledTaskEntity::class)->findAll())->count(1);

    $result = $this->cronWorkerScheduler->schedule('test', $nextRunDate);
    expect($result->getId())->equals($task->getId());
    expect($this->entityManager->getRepository(ScheduledTaskEntity::class)->findAll())->count(1);
  }

  public function testItDoesntScheduleRunningTaskImmediatelyIfRunning() {
    $nextRunDate = Carbon::now()->addWeek();
    $task = $this->cronWorkerScheduler->schedule('test', $nextRunDate);
    $task->setStatus(null);
    $this->entityManager->flush();
    $immediateTask = $this->cronWorkerScheduler->scheduleImmediatelyIfNotRunning('test');
    $tasks = $this->entityManager->getRepository(ScheduledTaskEntity::class)->findAll();
    expect($immediateTask->getId())->equals($task->getId());
    expect($tasks)->count(1);
    expect($tasks[0]->getType())->same('test');
    expect($tasks[0]->getStatus())->null();
    expect($tasks[0]->getScheduledAt())->same($nextRunDate);
  }

  public function testItRescheduleScheduledTaskImmediatelyIfNotRunning() {
    $nextRunDate = Carbon::now()->addWeek();
    $task = $this->cronWorkerScheduler->schedule('test', $nextRunDate);
    $immediateTask = $this->cronWorkerScheduler->scheduleImmediatelyIfNotRunning('test');
    $tasks = $this->entityManager->getRepository(ScheduledTaskEntity::class)->findAll();
    expect($immediateTask->getId())->equals($task->getId());
    expect($tasks)->count(1);
    expect($tasks[0]->getType())->same('test');
    expect($tasks[0]->getStatus())->same(ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->tester->assertEqualDateTimes($tasks[0]->getScheduledAt(), Carbon::now(), 1);
  }

  public function testItScheduleTaskImmediatelyIfNotRunning() {
    $this->cronWorkerScheduler->scheduleImmediatelyIfNotRunning('test');
    $tasks = $this->entityManager->getRepository(ScheduledTaskEntity::class)->findAll();
    expect($tasks)->count(1);
    expect($tasks[0]->getType())->equals('test');
    expect($tasks[0]->getStatus())->equals(ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->tester->assertEqualDateTimes($tasks[0]->getScheduledAt(), Carbon::now(), 1);
  }

  public function testItReschedulesTask() {
    $nextRunDate = Carbon::now()->subDay();
    $task = $this->cronWorkerScheduler->schedule('test', $nextRunDate);
    $this->cronWorkerScheduler->reschedule($task, 10);

    $tasks = $this->entityManager->getRepository(ScheduledTaskEntity::class)->findAll();
    expect($tasks)->count(1);
    expect($tasks[0]->getType())->same('test');
    expect($tasks[0]->getStatus())->same(ScheduledTaskEntity::STATUS_SCHEDULED);
    expect($tasks[0]->getScheduledAt())->greaterThan($nextRunDate);
    expect($tasks[0]->getScheduledAt())->greaterThan(Carbon::now());
  }

  public function testItCanRescheduleTasksProgressively() {
    $task = $this->scheduledTaskFactory->create('test', null, new Carbon());
    $scheduledAt = $task->getScheduledAt();

    $timeout = $this->cronWorkerScheduler->rescheduleProgressively($task);
    expect($timeout)->equals(ScheduledTaskEntity::BASIC_RESCHEDULE_TIMEOUT);
    expect($scheduledAt < $task->getScheduledAt())->true();
    expect($task->getStatus())->equals(ScheduledTaskEntity::STATUS_SCHEDULED);

    $timeout = $this->cronWorkerScheduler->rescheduleProgressively($task);
    expect($timeout)->equals(ScheduledTaskEntity::BASIC_RESCHEDULE_TIMEOUT * 2);

    $task->setRescheduleCount(123456); // too many
    $timeout = $this->cronWorkerScheduler->rescheduleProgressively($task);
    expect($timeout)->equals(ScheduledTaskEntity::MAX_RESCHEDULE_TIMEOUT);
  }
}
