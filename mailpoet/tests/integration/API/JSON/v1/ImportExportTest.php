<?php

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\v1\ImportExport;
use MailPoet\Cron\Workers\WooCommerceSync;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class ImportExportTest extends \MailPoetTest {

  /** @var ImportExport */
  private $endpoint;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  public function _before() {
    parent::_before();
    $this->endpoint = ContainerWrapper::getInstance()->get(ImportExport::class);
    $this->scheduledTasksRepository = ContainerWrapper::getInstance()->get(ScheduledTasksRepository::class);
    $this->entityManager->createQueryBuilder()
      ->delete(ScheduledTaskEntity::class, 's')
      ->where('s.type = :type')
      ->setParameter(':type', WooCommerceSync::TASK_TYPE )
      ->getQuery()
      ->execute();
  }

  public function testItSchedulesTaskWhenNoneExists() {
    $response = $this->endpoint->setupWooCommerceInitialImport();
    expect($response->status)->equals(200);
    $task = $this->scheduledTasksRepository->findOneBy(['type' => WooCommerceSync::TASK_TYPE]);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    expect($task->getStatus())->equals(ScheduledTaskEntity::STATUS_SCHEDULED);
    $now = time();
    $scheduledAt = new Carbon($task->getScheduledAt());
    expect($scheduledAt->timestamp)->greaterOrEquals($now - 1);
    expect($scheduledAt->timestamp)->lessOrEquals($now + 1);
  }

  public function testItReschedulesScheduledTaskToNow() {
    $originalSchedule = Carbon::createFromTimestamp((int)current_time('timestamp') + 3000);
    $this->createTask(WooCommerceSync::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, $originalSchedule);
    $this->entityManager->clear();
    $this->endpoint->setupWooCommerceInitialImport();
    $task = $this->scheduledTasksRepository->findOneBy(['type' => WooCommerceSync::TASK_TYPE]);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    expect($task->getStatus())->equals(ScheduledTaskEntity::STATUS_SCHEDULED);
    $now = time();
    $scheduledAt = new Carbon($task->getScheduledAt());
    expect($scheduledAt->timestamp)->greaterOrEquals($now - 1);
    expect($scheduledAt->timestamp)->lessOrEquals($now + 1);
    $taskCount = $this->scheduledTasksRepository->countBy(['type' => WooCommerceSync::TASK_TYPE]);
    expect($taskCount)->equals(1);
  }

  public function testItDoesNothingForRunningTask() {
    $this->createTask(WooCommerceSync::TASK_TYPE, null);
    $this->endpoint->setupWooCommerceInitialImport();
    $task = $this->scheduledTasksRepository->findOneBy(['type' => WooCommerceSync::TASK_TYPE]);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    expect($task->getStatus())->equals(null);
    $taskCount = $this->scheduledTasksRepository->countBy(['type' => WooCommerceSync::TASK_TYPE]);
    expect($taskCount)->equals(1);
  }

  public function testItIgnoresCompletedAndPausedTasks() {
    $this->createTask(WooCommerceSync::TASK_TYPE, ScheduledTaskEntity::STATUS_PAUSED);
    $this->createTask(WooCommerceSync::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED);
    $this->endpoint->setupWooCommerceInitialImport();
    $taskCount = $this->scheduledTasksRepository->countBy(['type' => WooCommerceSync::TASK_TYPE]);
    expect($taskCount)->equals(3);
  }

  private function createTask($type, $status = null, $scheduledAt = null) {
    if (!$scheduledAt) {
      Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    }
    $task = new ScheduledTaskEntity();
    $task->setType($type);
    $task->setStatus($status);
    $task->setScheduledAt($scheduledAt);
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();
  }
}
