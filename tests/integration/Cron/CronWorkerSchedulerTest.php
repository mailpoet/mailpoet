<?php

namespace MailPoet\Test\Cron;

use MailPoet\Cron\CronWorkerScheduler;
use MailPoet\Models\ScheduledTask;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

require_once __DIR__ . '/Workers/SimpleWorkerMockImplementation.php';

class CronWorkerSchedulerTest extends \MailPoetTest {
  /** @var CronWorkerScheduler */
  private $cron_worker_scheduler;

  public function _before() {
    $this->cron_worker_scheduler = $this->di_container->get(CronWorkerScheduler::class);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
  }

  public function testItSchedulesTask() {
    $next_run_date = Carbon::now()->addWeek();
    $this->cron_worker_scheduler->schedule('test', $next_run_date);

    $tasks = ScheduledTask::findMany();
    expect($tasks)->count(1);
    expect($tasks[0]->type)->same('test');
    expect($tasks[0]->status)->same(ScheduledTask::STATUS_SCHEDULED);
    expect($tasks[0]->scheduled_at)->same($next_run_date->format('Y-m-d H:i:s'));
  }

  public function testItDoesNotScheduleTaskTwice() {
    $next_run_date = Carbon::now()->addWeek();
    $this->cron_worker_scheduler->schedule('test', $next_run_date);
    expect(ScheduledTask::findMany())->count(1);

    $result = $this->cron_worker_scheduler->schedule('test', $next_run_date);
    expect($result)->false();
    expect(ScheduledTask::findMany())->count(1);
  }

  public function testItReschedulesTask() {
    $next_run_date = Carbon::now()->subDay();
    $task = $this->cron_worker_scheduler->schedule('test', $next_run_date);
    $this->cron_worker_scheduler->reschedule($task, 10);

    $tasks = ScheduledTask::findMany();
    expect($tasks)->count(1);
    expect($tasks[0]->type)->same('test');
    expect($tasks[0]->status)->same(ScheduledTask::STATUS_SCHEDULED);
    expect($tasks[0]->scheduled_at)->greaterThan($next_run_date);
    expect($tasks[0]->scheduled_at)->greaterThan(Carbon::now());
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
  }
}
