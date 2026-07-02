<?php declare(strict_types = 1);

namespace MailPoet\Settings;

use MailPoet\Cron\Workers\InactiveSubscribersMaintenance;
use MailPoet\Cron\Workers\WooCommerceSync;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Services\Bridge;
use MailPoet\Services\SubscribersCountReporter;
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

    $this->settingsChangeHandler->onSubscribeOldWoocommerceCustomersChange();

    $this->entityManager->clear();
    $task = $this->getScheduledTaskByType(WooCommerceSync::TASK_TYPE);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $scheduledAt = $task->getScheduledAt();
    $this->assertInstanceOf(\DateTime::class, $scheduledAt);
    $expectedScheduledAt = Carbon::now()->millisecond(0);
    $expectedScheduledAt->subMinute();
    $this->tester->assertEqualDateTimes($task->getScheduledAt(), $expectedScheduledAt, 1);
    verify($newTask->getId())->equals($task->getId());
  }

  public function testItCreatesScheduledTaskForWoocommerceSync(): void {
    $task = $this->getScheduledTaskByType(WooCommerceSync::TASK_TYPE);
    verify($task)->null();
    $this->settingsChangeHandler->onSubscribeOldWoocommerceCustomersChange();
    $task = $this->getScheduledTaskByType(WooCommerceSync::TASK_TYPE);
    verify($task)->instanceOf(ScheduledTaskEntity::class);
  }

  public function testItReplacesPendingScheduledTaskForInactiveSubscribers(): void {
    $oldTask = $this->createScheduledTask(InactiveSubscribersMaintenance::TASK_TYPE);
    $oldTask->setScheduledAt(Carbon::now()->addDay());
    $oldTask->setMeta(['last_subscriber_id' => 500]);
    $this->tasksRepository->flush();
    $oldTaskId = (int)$oldTask->getId();

    $this->settingsChangeHandler->onInactiveSubscribersIntervalChange();
    $this->entityManager->clear();

    // The partially-progressed task is dropped and replaced with a fresh one starting from 0.
    verify($this->tasksRepository->findOneById($oldTaskId))->null();
    $task = $this->getScheduledTaskByType(InactiveSubscribersMaintenance::TASK_TYPE);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    verify((int)$task->getId())->notEquals($oldTaskId);
    verify($task->getMeta())->null();
    $scheduledAt = $task->getScheduledAt();
    $this->assertInstanceOf(\DateTime::class, $scheduledAt);
    $expectedScheduledAt = Carbon::now()->millisecond(0);
    $expectedScheduledAt->subMinute();
    $this->tester->assertEqualDateTimes($scheduledAt, $expectedScheduledAt, 1);
  }

  public function testItReplacesInProgressTaskForInactiveSubscribers(): void {
    $runningTask = $this->createScheduledTask(InactiveSubscribersMaintenance::TASK_TYPE);
    $runningTask->setStatus(null);
    $runningTask->setMeta(['last_subscriber_id' => 500]);
    $this->tasksRepository->flush();
    $runningTaskId = (int)$runningTask->getId();

    $this->settingsChangeHandler->onInactiveSubscribersIntervalChange();
    $this->entityManager->clear();

    // An in-progress run (null status) is removed too, so it can't resume mid-way on the old interval.
    verify($this->tasksRepository->findOneById($runningTaskId))->null();
    $task = $this->getScheduledTaskByType(InactiveSubscribersMaintenance::TASK_TYPE);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    verify((int)$task->getId())->notEquals($runningTaskId);
  }

  public function testItCreatesScheduledTaskForInactiveSubscribers(): void {
    $task = $this->getScheduledTaskByType(InactiveSubscribersMaintenance::TASK_TYPE);
    verify($task)->null();
    $this->settingsChangeHandler->onInactiveSubscribersIntervalChange();
    $task = $this->getScheduledTaskByType(InactiveSubscribersMaintenance::TASK_TYPE);
    verify($task)->instanceOf(ScheduledTaskEntity::class);
  }

  private function createScheduledTask(string $type): ScheduledTaskEntity {
    $task = new ScheduledTaskEntity();
    $task->setType($type);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->tasksRepository->persist($task);
    $this->tasksRepository->flush();
    return $task;
  }

  public function testItChecksAndStoresKeysWhenUpdatingBridge() {
    $key = 'valid-key';
    $settings = [];
    $settings[Mailer::MAILER_CONFIG_SETTING_NAME]['mailpoet_api_key'] = $key;
    $settings['premium']['premium_key'] = $key;
    $response = ['state' => Bridge::KEY_VALID];

    $bridge = $this->createMock(Bridge::class);
    $bridge->expects($this->once())
      ->method('checkMSSKey')
      ->with($this->equalTo($key))
      ->willReturn($response);
    $bridge->expects($this->once())
      ->method('storeMSSKeyAndState')
      ->with(
        $this->equalTo($key),
        $this->equalTo($response)
      );

    $bridge->expects($this->once())
      ->method('checkPremiumKey')
      ->with($this->equalTo($key))
      ->willReturn($response);
    $bridge->expects($this->once())
      ->method('storePremiumKeyAndState')
      ->with(
        $this->equalTo($key),
        $this->equalTo($response)
      );

    $countReporterMock = $this->createMock(SubscribersCountReporter::class);
    $countReporterMock->expects($this->once())
      ->method('report')
      ->with($this->equalTo($key));

    $changeHandler = $this->getServiceWithOverrides(SettingsChangeHandler::class, [
      'bridge' => $bridge,
      'subscribersCountReporter' => $countReporterMock,
    ]);

    $changeHandler->updateApiKeyState($settings);
  }

  private function getScheduledTaskByType(string $type): ?ScheduledTaskEntity {
    return $this->tasksRepository->findOneBy([
      'type' => $type,
      'status' => ScheduledTaskEntity::STATUS_SCHEDULED,
    ]);
  }
}
