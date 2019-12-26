<?php

namespace MailPoet\Test\Settings;

use Codeception\Stub;
use MailPoet\Entities\SettingEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;

class SettingsControllerTest extends \MailPoetTest {

  /** @var SettingsController */
  private $controller;

  public function _before() {
    parent::_before();
    $this->controller = $this->di_container->get(SettingsController::class);
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
      'settings_repository' => $this->make(SettingsRepository::class, [
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
    $db_value = $this->getSettingValue('test_key');
    $this->assertEquals(1, $db_value);
  }

  public function testItCanSetAtNestedLevel() {
    $this->controller->set('test_key.key1.key2', 1);
    $this->assertEquals(1, $this->controller->get('test_key.key1.key2'));
    $db_value = unserialize($this->getSettingValue('test_key'));
    $this->assertEquals(1, $db_value['key1']['key2']);
  }

  public function testItCanSetNUll() {
    $this->controller->set('test_key.key1.key2', 1);
    $this->assertEquals(1, $this->controller->get('test_key.key1.key2'));
    $this->controller->set('test_key.key1.key2', null);
    $this->assertNull(null, $this->controller->get('test_key.key1.key2'));
    $db_value = unserialize($this->getSettingValue('test_key'));
    $this->assertNull($db_value['key1']['key2']);
  }

  public function testItCanOverrideValueAndSetAtNestedLevel() {
    $this->controller->set('test_key.key1', 1);
    $this->controller->set('test_key.key1.key2', 1);
    $this->assertEquals(1, $this->controller->get('test_key.key1.key2'));
    $db_value = unserialize($this->getSettingValue('test_key'));
    $this->assertEquals(1, $db_value['key1']['key2']);
  }

  public function testItLoadsFromDbOnlyOnce() {
    $this->createOrUpdateSetting('test_key', 1);
    $this->assertEquals(1, $this->controller->get('test_key'));
    $this->createOrUpdateSetting('test_key', 2);
    $this->assertEquals(1, $this->controller->get('test_key'));
    $this->assertEquals(true, true);
  }

  public function _after() {
    $table_name = $this->entity_manager->getClassMetadata(SettingEntity::class)->getTableName();
    $this->connection->executeUpdate('TRUNCATE ' . $table_name);
  }

  private function createOrUpdateSetting($name, $value) {
    $table_name = $this->entity_manager->getClassMetadata(SettingEntity::class)->getTableName();
    $this->connection->executeUpdate("
      INSERT INTO $table_name (name, value) VALUES (?, ?)
      ON DUPLICATE KEY UPDATE value = ?
    ", [$name, $value, $value]);
  }

  private function getSettingValue($name) {
    $table_name = $this->entity_manager->getClassMetadata(SettingEntity::class)->getTableName();
    return $this->connection->executeQuery("SELECT value FROM $table_name WHERE name = ?", [$name])->fetchColumn();
  }
}
