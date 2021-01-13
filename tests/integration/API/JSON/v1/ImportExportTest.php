<?php

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\v1\ImportExport;
use MailPoet\Cron\Workers\WooCommerceSync;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\ScheduledTask;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class ImportExportTest extends \MailPoetTest {

  /** @var ImportExport */
  private $endpoint;

  public function _before() {
    parent::_before();
    $this->endpoint = ContainerWrapper::getInstance()->get(ImportExport::class);
    ScheduledTask::where('type', WooCommerceSync::TASK_TYPE)->deleteMany();
  }

  public function testItSchedulesTaskWhenNoneExistss() {
    $response = $this->endpoint->setupWooCommerceInitialImport();
    expect($response->status)->equals(200);
    $task = ScheduledTask::where('type', WooCommerceSync::TASK_TYPE)->findOne();
    assert($task instanceof ScheduledTask);
    expect($task->status)->equals(ScheduledTask::STATUS_SCHEDULED);
    $now = time();
    $scheduledAt = new Carbon($task->scheduledAt);
    expect($scheduledAt->timestamp)->greaterOrEquals($now - 1);
    expect($scheduledAt->timestamp)->lessOrEquals($now + 1);
  }

  public function testItReschedulesScheduledTaskToNow() {
    $originalSchedule = Carbon::createFromTimestamp(time() + 3000);
    $this->createTask(WooCommerceSync::TASK_TYPE, ScheduledTask::STATUS_SCHEDULED, $originalSchedule);
    $this->endpoint->setupWooCommerceInitialImport();
    $task = ScheduledTask::where('type', WooCommerceSync::TASK_TYPE)->findOne();
    assert($task instanceof ScheduledTask);
    expect($task->status)->equals(ScheduledTask::STATUS_SCHEDULED);
    $now = time();
    $scheduledAt = new Carbon($task->scheduledAt);
    expect($scheduledAt->timestamp)->greaterOrEquals($now - 1);
    expect($scheduledAt->timestamp)->lessOrEquals($now + 1);
    $taskCount = ScheduledTask::where('type', WooCommerceSync::TASK_TYPE)->count();
    expect($taskCount)->equals(1);
  }

  public function testItDoesNothingForRunningTask() {
    $this->createTask(WooCommerceSync::TASK_TYPE, null);
    $this->endpoint->setupWooCommerceInitialImport();
    $task = ScheduledTask::where('type', WooCommerceSync::TASK_TYPE)->findOne();
    assert($task instanceof ScheduledTask);
    expect($task->status)->equals(null);
    $taskCount = ScheduledTask::where('type', WooCommerceSync::TASK_TYPE)->count();
    expect($taskCount)->equals(1);
  }

  public function testItIgnoresCompletedAndPausedTasks() {
    $this->createTask(WooCommerceSync::TASK_TYPE, ScheduledTask::STATUS_PAUSED);
    $this->createTask(WooCommerceSync::TASK_TYPE, ScheduledTask::STATUS_COMPLETED);
    $this->endpoint->setupWooCommerceInitialImport();
    $taskCount = ScheduledTask::where('type', WooCommerceSync::TASK_TYPE)->count();
    expect($taskCount)->equals(3);
  }

  private function createTask($type, $status = null, $scheduledAt = null) {
    if (!$scheduledAt) {
      Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    }
    $task = ScheduledTask::create();
    $task->type = $type;
    $task->status = $status;
    $task->scheduledAt = $scheduledAt;
    $task->save();
    return $task;
  }
}
