<?php

namespace MailPoet\Test\Cron;

use MailPoet\Cron\CronWorkerScheduler;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Models\ScheduledTask;
use MailPoetVendor\Carbon\Carbon;

require_once __DIR__ . '/Workers/SimpleWorkerMockImplementation.php';

class CronWorkerSchedulerTest extends \MailPoetTest {
  /** @var CronWorkerScheduler */
  private $cronWorkerScheduler;

  public function _before() {
    $this->cronWorkerScheduler = $this->diContainer->get(CronWorkerScheduler::class);
    $this->truncateEntity(ScheduledTaskEntity::class);
  }

  public function testItSchedulesTask() {
    $nextRunDate = Carbon::now()->addWeek();
    $this->cronWorkerScheduler->schedule('test', $nextRunDate);
    $tasks = $this->entityManager->getRepository(ScheduledTaskEntity::class)->findAll();
    expect($tasks)->count(1);
    expect($tasks[0]->getType())->same('test');
    expect($tasks[0]->getStatus())->same(ScheduledTask::STATUS_SCHEDULED);
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

  public function testItReschedulesTask() {
    $nextRunDate = Carbon::now()->subDay();
    $task = $this->cronWorkerScheduler->schedule('test', $nextRunDate);
    $oldModel = ScheduledTask::findOne($task->getId());
    $this->assertInstanceOf(ScheduledTask::class, $oldModel);
    $this->cronWorkerScheduler->reschedule($oldModel, 10);
    $tasks = $this->entityManager->getRepository(ScheduledTaskEntity::class)->findAll();
    $this->entityManager->refresh($task);
    expect($tasks)->count(1);
    expect($tasks[0]->getType())->same('test');
    expect($tasks[0]->getStatus())->same(ScheduledTask::STATUS_SCHEDULED);
    expect($tasks[0]->getScheduledAt())->greaterThan($nextRunDate);
    expect($tasks[0]->getScheduledAt())->greaterThan(Carbon::now());
  }

  public function _after() {
    $this->truncateEntity(ScheduledTaskEntity::class);
  }
}
