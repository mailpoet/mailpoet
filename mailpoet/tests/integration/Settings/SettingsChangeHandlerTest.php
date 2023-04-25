<?php declare(strict_types = 1);

namespace MailPoet\Settings;

use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\Cron\Workers\WooCommerceSync;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Services\Bridge;
use MailPoet\Services\SubscribersCountReporter;
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

    $changeHandler->updateBridge($settings);
  }

  private function getScheduledTaskByType(string $type): ?ScheduledTaskEntity {
    return $this->tasksRepository->findOneBy([
      'type' => $type,
      'status' => ScheduledTaskEntity::STATUS_SCHEDULED,
    ]);
  }
}
