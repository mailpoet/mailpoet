<?php

namespace MailPoet\Test\API\JSON\v1;

use Carbon\Carbon;
use MailPoet\API\JSON\v1\ImportExport;
use MailPoet\Cron\Workers\WooCommerceSync;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\ScheduledTask;

class ImportExportTest extends \MailPoetTest {

  /** @var ImportExport */
  private $endpoint;

  function _before() {
    parent::_before();
    $this->endpoint = ContainerWrapper::getInstance()->get(ImportExport::class);
    ScheduledTask::where('type', WooCommerceSync::TASK_TYPE)->deleteMany();
  }

  function testItSchedulesTaskWhenNoneExistss() {
    $response = $this->endpoint->setupWooCommerceInitialImport();
    expect($response->status)->equals(200);
    $task = ScheduledTask::where('type', WooCommerceSync::TASK_TYPE)->findOne();
    expect($task->status)->equals(ScheduledTask::STATUS_SCHEDULED);
    $now = time();
    $scheduled_at = new Carbon($task->scheduled_at);
    expect($scheduled_at->timestamp)->greaterOrEquals($now - 1);
    expect($scheduled_at->timestamp)->lessOrEquals($now + 1);
  }

  function testItReschedulesScheduledTaskToNow() {
    $original_schedule = Carbon::createFromTimestamp(time() + 3000);
    $this->createTask(WooCommerceSync::TASK_TYPE, ScheduledTask::STATUS_SCHEDULED, $original_schedule);
    $this->endpoint->setupWooCommerceInitialImport();
    $task = ScheduledTask::where('type', WooCommerceSync::TASK_TYPE)->findOne();
    expect($task->status)->equals(ScheduledTask::STATUS_SCHEDULED);
    $now = time();
    $scheduled_at = new Carbon($task->scheduled_at);
    expect($scheduled_at->timestamp)->greaterOrEquals($now - 1);
    expect($scheduled_at->timestamp)->lessOrEquals($now + 1);
    $task_count = ScheduledTask::where('type', WooCommerceSync::TASK_TYPE)->count();
    expect($task_count)->equals(1);
  }

  function testItDoesNothingForRunningTask() {
    $this->createTask(WooCommerceSync::TASK_TYPE, null);
    $this->endpoint->setupWooCommerceInitialImport();
    $task = ScheduledTask::where('type', WooCommerceSync::TASK_TYPE)->findOne();
    expect($task->status)->equals(null);
    $task_count = ScheduledTask::where('type', WooCommerceSync::TASK_TYPE)->count();
    expect($task_count)->equals(1);
  }

  function testItIgnoresCompletedAndPausedTasks() {
    $this->createTask(WooCommerceSync::TASK_TYPE, ScheduledTask::STATUS_PAUSED);
    $this->createTask(WooCommerceSync::TASK_TYPE, ScheduledTask::STATUS_COMPLETED);
    $this->endpoint->setupWooCommerceInitialImport();
    $task_count = ScheduledTask::where('type', WooCommerceSync::TASK_TYPE)->count();
    expect($task_count)->equals(3);
  }

  private function createTask($type, $status = null, $scheduled_at = null) {
    if (!$scheduled_at) {
      Carbon::createFromTimestamp(current_time('timestamp'));
    }
    $task = ScheduledTask::create();
    $task->type = $type;
    $task->status = $status;
    $task->scheduled_at = $scheduled_at;
    $task->save();
    return $task;
  }
}
