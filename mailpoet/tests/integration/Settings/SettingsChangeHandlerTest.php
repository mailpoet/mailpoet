<?php

namespace MailPoet\Settings;

use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\Cron\Workers\WooCommerceSync;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class SettingsChangeHandlerTest extends \MailPoetTest {
  /** @var ScheduledTasksRepository */
  private $tasksRepository;

  /** @var SettingsChangeHandler */
  private $settingsChangeHandler;

  public function _before() {
    parent::_before();
    $this->tasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->settingsChangeHandler = $this->diContainer->get(SettingsChangeHandler::class);
  }

  public function testItReschedulesScheduledTaskForWoocommerceSync(): void {
    $newTask = $this->createScheduledTask(WooCommerceSync::TASK_TYPE);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $newTask);

    $this->settingsChangeHandler->onSubscribeOldWoocommerceCustomersChange();

    $this->entityManager->clear();
    $task = $this->getScheduledTaskByType(WooCommerceSync::TASK_TYPE);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $scheduledAt = $task->getScheduledAt();
    $this->assertInstanceOf(\DateTime::class, $scheduledAt);
    $expectedScheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $expectedScheduledAt->subMinute();
    $this->tester->assertEqualDateTimes($task->getScheduledAt(), $expectedScheduledAt, 1);
    expect($newTask->getId())->equals($task->getId());
  }

  public function testItCreatesScheduledTaskForWoocommerceSync(): void {
    $task = $this->getScheduledTaskByType(WooCommerceSync::TASK_TYPE);
    expect($task)->null();
    $this->settingsChangeHandler->onSubscribeOldWoocommerceCustomersChange();
    $task = $this->getScheduledTaskByType(WooCommerceSync::TASK_TYPE);
    expect($task)->isInstanceOf(ScheduledTaskEntity::class);
  }

  public function testItReschedulesScheduledTaskForInactiveSubscribers(): void {
    $newTask = $this->createScheduledTask(InactiveSubscribers::TASK_TYPE);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $newTask);
    $this->settingsChangeHandler->onInactiveSubscribersIntervalChange();

    $task = $this->getScheduledTaskByType(InactiveSubscribers::TASK_TYPE);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $scheduledAt = $task->getScheduledAt();
    $this->assertInstanceOf(\DateTime::class, $scheduledAt);
    $expectedScheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $expectedScheduledAt->subMinute();
    $this->tester->assertEqualDateTimes($task->getScheduledAt(), $expectedScheduledAt, 1);
    expect($newTask->getId())->equals($task->getId());
  }

  public function testItCreatesScheduledTaskForInactiveSubscribers(): void {
    $task = $this->getScheduledTaskByType(InactiveSubscribers::TASK_TYPE);
    expect($task)->null();
    $this->settingsChangeHandler->onInactiveSubscribersIntervalChange();
    $task = $this->getScheduledTaskByType(InactiveSubscribers::TASK_TYPE);
    expect($task)->isInstanceOf(ScheduledTaskEntity::class);
  }

  private function createScheduledTask(string $type): ScheduledTaskEntity {
    $task = new ScheduledTaskEntity();
    $task->setType($type);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->tasksRepository->persist($task);
    $this->tasksRepository->flush();
    return $task;
  }

  private function getScheduledTaskByType(string $type): ?ScheduledTaskEntity {
    return $this->tasksRepository->findOneBy([
      'type' => $type,
      'status' => ScheduledTaskEntity::STATUS_SCHEDULED,
    ]);
  }
}
