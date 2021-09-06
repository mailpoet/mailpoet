<?php

namespace MailPoet\Test\Cron;

use MailPoet\Cron\CronWorkerScheduler;
use MailPoet\Models\ScheduledTask;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

require_once __DIR__ . '/Workers/SimpleWorkerMockImplementation.php';

class CronWorkerSchedulerTest extends \MailPoetTest {
  /** @var CronWorkerScheduler */
  private $cronWorkerScheduler;

  public function _before() {
    $this->cronWorkerScheduler = $this->diContainer->get(CronWorkerScheduler::class);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
  }

  public function testItSchedulesTask() {
    $nextRunDate = Carbon::now()->addWeek();
    $this->cronWorkerScheduler->schedule('test', $nextRunDate);

    $tasks = ScheduledTask::findMany();
    expect($tasks)->count(1);
    expect($tasks[0]->type)->same('test');
    expect($tasks[0]->status)->same(ScheduledTask::STATUS_SCHEDULED);
    expect($tasks[0]->scheduled_at)->same($nextRunDate->format('Y-m-d H:i:s'));
  }

  public function testItDoesNotScheduleTaskTwice() {
    $nextRunDate = Carbon::now()->addWeek();
    $task = $this->cronWorkerScheduler->schedule('test', $nextRunDate);
    expect(ScheduledTask::findMany())->count(1);

    $result = $this->cronWorkerScheduler->schedule('test', $nextRunDate);
    expect($result->getId())->equals($task->getId());
    expect(ScheduledTask::findMany())->count(1);
  }

  public function testItReschedulesTask() {
    $nextRunDate = Carbon::now()->subDay();
    $task = $this->cronWorkerScheduler->schedule('test', $nextRunDate);
    $oldModel = ScheduledTask::findOne($task->getId());
    $this->assertInstanceOf(ScheduledTask::class, $oldModel);
    $this->cronWorkerScheduler->reschedule($oldModel, 10);
    $tasks = ScheduledTask::findMany();
    expect($tasks)->count(1);
    expect($tasks[0]->type)->same('test');
    expect($tasks[0]->status)->same(ScheduledTask::STATUS_SCHEDULED);
    expect($tasks[0]->scheduled_at)->greaterThan($nextRunDate);
    expect($tasks[0]->scheduled_at)->greaterThan(Carbon::now());
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
  }
}
