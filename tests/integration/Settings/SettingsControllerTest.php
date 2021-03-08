<?php

namespace MailPoet\Test\Settings;

use Codeception\Stub;
use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\Cron\Workers\WooCommerceSync;
use MailPoet\Entities\FormEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SettingEntity;
use MailPoet\Form\FormsRepository;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class SettingsControllerTest extends \MailPoetTest {

  /** @var SettingsController */
  private $controller;

  /** @var ScheduledTasksRepository */
  private $tasksRepository;

  /** @var FormsRepository */
  private $formsReposittory;

  public function _before() {
    parent::_before();
    $this->controller = $this->diContainer->get(SettingsController::class);
    $this->tasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->formsReposittory = $this->diContainer->get(FormsRepository::class);
    $this->clear();
  }

  public function testItReturnsStoredValue() {
    $this->createOrUpdateSetting('test_key', 1);
    $this->controller->resetCache();
    $this->assertEquals(1, $this->controller->get('test_key'));
  }

  public function testItReturnsStoredNestedValue() {
    $this->createOrUpdateSetting('test_key', serialize(['sub_key' => 'value']));
    $this->assertEquals('value', $this->controller->get('test_key.sub_key'));
  }

  public function testItReturnsNullForUnknownSetting() {
    $this->assertEquals(null, $this->controller->get('test_key'));
    $this->assertEquals(null, $this->controller->get('test_key.sub_key'));
    $this->createOrUpdateSetting('test_key', serialize(['sub_key' => 'value']));
    $this->assertEquals(null, $this->controller->get('test_key.wrong_subkey'));
  }

  public function testItReturnsDefaultValueForUnknownSetting() {
    $this->assertEquals('default', $this->controller->get('test_key', 'default'));
    $this->assertEquals('default', $this->controller->get('test_key.sub_key', 'default'));
    $this->createOrUpdateSetting('test_key', serialize(['sub_key' => 'value']));
    $this->assertEquals('default', $this->controller->get('test_key.wrong_subkey', 'default'));
  }

  public function testItCanFetchValuesFromDB() {
    $this->assertEquals(null, $this->controller->fetch('test_key'));
    $this->assertEquals(null, $this->controller->fetch('test_key.sub_key'));
    $this->assertEquals('default', $this->controller->fetch('test_key.wrong_subkey', 'default'));
    $this->createOrUpdateSetting('test_key', serialize(['sub_key' => 'value']));
    $this->assertEquals('default', $this->controller->get('test_key.sub_key', 'default'));
    $this->assertEquals('value', $this->controller->fetch('test_key.sub_key', 'default'));
  }

  public function testItReturnsDefaultValueAsFallback() {
    $settings = Stub::make($this->controller, [
      'settingsRepository' => $this->make(SettingsRepository::class, [
         'findOneByName' => null,
         'findAll' => [],
       ]),
      'getAllDefaults' => function () {
        return ['default1' => ['default2' => 1]];
      },
    ]);
    $settings->delete('default1');
    $value = $settings->get('default1');
    $this->assertEquals(1, $value['default2']);
    $this->assertEquals(1, $settings->get('default1.default2'));
  }

  public function testItCanReturnAllSettings() {
    $this->createOrUpdateSetting('test_key1', 1);
    $this->createOrUpdateSetting('test_key2', 2);
    $all = $this->controller->getAll();
    $this->assertEquals(1, $all['test_key1']);
    $this->assertEquals(2, $all['test_key2']);
  }

  public function testItCanSetAtTopLevel() {
    $this->controller->set('test_key', 1);
    $this->assertEquals(1, $this->controller->get('test_key'));
    $dbValue = $this->getSettingValue('test_key');
    $this->assertEquals(1, $dbValue);
  }

  public function testItCanSetAtNestedLevel() {
    $this->controller->set('test_key.key1.key2', 1);
    $this->assertEquals(1, $this->controller->get('test_key.key1.key2'));
    $dbValue = unserialize($this->getSettingValue('test_key'));
    $this->assertEquals(1, $dbValue['key1']['key2']);
  }

  public function testItCanSetNUll() {
    $this->controller->set('test_key.key1.key2', 1);
    $this->assertEquals(1, $this->controller->get('test_key.key1.key2'));
    $this->controller->set('test_key.key1.key2', null);
    $this->assertNull($this->controller->get('test_key.key1.key2'));
    $dbValue = unserialize($this->getSettingValue('test_key'));
    $this->assertNull($dbValue['key1']['key2']);
  }

  public function testItCanOverrideValueAndSetAtNestedLevel() {
    $this->controller->set('test_key.key1', 1);
    $this->controller->set('test_key.key1.key2', 1);
    $this->assertEquals(1, $this->controller->get('test_key.key1.key2'));
    $dbValue = unserialize($this->getSettingValue('test_key'));
    $this->assertEquals(1, $dbValue['key1']['key2']);
  }

  public function testItLoadsFromDbOnlyOnce() {
    $this->createOrUpdateSetting('test_key', 1);
    $this->assertEquals(1, $this->controller->get('test_key'));
    $this->createOrUpdateSetting('test_key', 2);
    $this->assertEquals(1, $this->controller->get('test_key'));
    $this->assertEquals(true, true);
  }

  public function testItReschedulesScheduledTaskForWoocommerceSync(): void {
    $newTask = $this->createScheduledTask(WooCommerceSync::TASK_TYPE);
    assert($newTask instanceof ScheduledTaskEntity);

    $this->controller->onSubscribeOldWoocommerceCustomersChange();

    $this->entityManager->clear();
    $task = $this->getScheduledTaskByType(WooCommerceSync::TASK_TYPE);
    assert($task instanceof ScheduledTaskEntity);
    $scheduledAt = $task->getScheduledAt();
    assert($scheduledAt instanceof \DateTime);
    $expectedScheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $expectedScheduledAt->subMinute();
    expect($scheduledAt)->equals($expectedScheduledAt);
    expect($newTask->getId())->equals($task->getId());
  }

  public function testItCreatesScheduledTaskForWoocommerceSync(): void {
    $task = $this->getScheduledTaskByType(WooCommerceSync::TASK_TYPE);
    expect($task)->null();
    $this->controller->onSubscribeOldWoocommerceCustomersChange();
    $task = $this->getScheduledTaskByType(WooCommerceSync::TASK_TYPE);
    expect($task)->isInstanceOf(ScheduledTaskEntity::class);
  }

  public function testItReschedulesScheduledTaskForInactiveSubscribers(): void {
    $newTask = $this->createScheduledTask(InactiveSubscribers::TASK_TYPE);
    assert($newTask instanceof ScheduledTaskEntity);
    $this->controller->onInactiveSubscribersIntervalChange();

    $task = $this->getScheduledTaskByType(InactiveSubscribers::TASK_TYPE);
    assert($task instanceof ScheduledTaskEntity);
    $scheduledAt = $task->getScheduledAt();
    assert($scheduledAt instanceof \DateTime);
    $expectedScheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $expectedScheduledAt->subMinute();
    expect($scheduledAt)->equals($expectedScheduledAt);
    expect($newTask->getId())->equals($task->getId());
  }

  public function testItCreatesScheduledTaskForInactiveSubscribers(): void {
    $task = $this->getScheduledTaskByType(InactiveSubscribers::TASK_TYPE);
    expect($task)->null();
    $this->controller->onInactiveSubscribersIntervalChange();
    $task = $this->getScheduledTaskByType(InactiveSubscribers::TASK_TYPE);
    expect($task)->isInstanceOf(ScheduledTaskEntity::class);
  }

  public function testItReturnsCorrectSuccessMessage(): void {
    $this->controller->set('signup_confirmation.enabled', 1);
    expect($this->controller->getDefaultSuccessMessage())->equals(__('Check your inbox or spam folder to confirm your subscription.', 'mailpoet'));
    $this->controller->set('signup_confirmation.enabled', 0);
    expect($this->controller->getDefaultSuccessMessage())->equals(__('You’ve been successfully subscribed to our newsletter!', 'mailpoet'));
  }

  public function testItUpdatesSuccessMessagesForForms(): void {
    $this->controller->set('signup_confirmation.enabled', 1);
    $form = new FormEntity('test form');
    $form->setSettings(['success_message' => __('Check your inbox or spam folder to confirm your subscription.', 'mailpoet')]);
    $this->entityManager->persist($form);
    $this->entityManager->flush();

    $this->controller->set('signup_confirmation.enabled', 0);
    $this->controller->updateSuccessMessages();
    $forms = $this->formsReposittory->findAll();
    expect($forms)->count(1);
    foreach ($forms as $form) {
      expect($form->getSettings()['success_message'] ?? null)->equals(__('You’ve been successfully subscribed to our newsletter!', 'mailpoet'));
    }

    $this->controller->set('signup_confirmation.enabled', 1);
    $this->controller->updateSuccessMessages();
    $forms = $this->formsReposittory->findAll();
    expect($forms)->count(1);
    foreach ($forms as $form) {
      expect($form->getSettings()['success_message'] ?? null)->equals(__('Check your inbox or spam folder to confirm your subscription.', 'mailpoet'));
    }
  }

  private function clear() {
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(SettingEntity::class);
    $this->truncateEntity(FormEntity::class);
  }

  public function _after() {
    $this->clear();
  }

  private function getScheduledTaskByType(string $type): ?ScheduledTaskEntity {
    return $this->tasksRepository->findOneBy([
      'type' => $type,
      'status' => ScheduledTaskEntity::STATUS_SCHEDULED,
    ]);
  }

  private function createScheduledTask(string $type): ScheduledTaskEntity {
    $task = new ScheduledTaskEntity();
    $task->setType($type);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->tasksRepository->persist($task);
    $this->tasksRepository->flush();
    return $task;
  }

  private function createOrUpdateSetting($name, $value) {
    $tableName = $this->entityManager->getClassMetadata(SettingEntity::class)->getTableName();
    $this->connection->executeUpdate("
      INSERT INTO $tableName (name, value) VALUES (?, ?)
      ON DUPLICATE KEY UPDATE value = ?
    ", [$name, $value, $value]);
  }

  private function getSettingValue($name) {
    $tableName = $this->entityManager->getClassMetadata(SettingEntity::class)->getTableName();
    return $this->connection->executeQuery("SELECT value FROM $tableName WHERE name = ?", [$name])->fetchColumn();
  }
}
