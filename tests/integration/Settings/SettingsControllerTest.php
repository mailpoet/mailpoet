<?php
namespace MailPoet\Test\Settings;

use Codeception\Stub;
use MailPoet\Models\Setting;
use MailPoet\Settings\SettingsController;

class SettingsControllerTest extends \MailPoetTest {

  /** @var SettingsController */
  private $controller;

  function _before() {
    $this->controller = new SettingsController();
  }

  function testItReturnsStoredValue() {
    Setting::setValue('test_key', 1);
    $this->assertEquals(1, $this->controller->get('test_key'));
  }

  function testItReturnsStoredNestedValue() {
    Setting::setValue('test_key', ['sub_key' => 'value']);
    $this->assertEquals('value', $this->controller->get('test_key.sub_key'));
  }

  function testItReturnsNullForUnknownSetting() {
    $this->assertEquals(null, $this->controller->get('test_key'));
    $this->assertEquals(null, $this->controller->get('test_key.sub_key'));
    Setting::setValue('test_key', ['sub_key' => 'value']);
    $this->assertEquals(null, $this->controller->get('test_key.wrong_subkey'));
  }

  function testItReturnsDefaultValueForUnknownSetting() {
    $this->assertEquals('default', $this->controller->get('test_key', 'default'));
    $this->assertEquals('default', $this->controller->get('test_key.sub_key', 'default'));
    Setting::setValue('test_key', ['sub_key' => 'value']);
    $this->assertEquals('default', $this->controller->get('test_key.wrong_subkey', 'default'));
  }

  function testItReturnsDefaultValueAsFallback() {
    $settings = Stub::make($this->controller, [
      'getDefaults' => function () {
        return ['default1' => ['default2' => 1]];
      }
    ]);
    $settings->delete('default1');
    $value = $settings->get('default1');
    $this->assertEquals(1, $value['default2']);
    $this->assertEquals(1, $settings->get('default1.default2'));
  }

  function testItCanReturnAllSettings() {
    Setting::setValue('test_key1', 1);
    Setting::setValue('test_key2', 2);
    $all = $this->controller->getAll();
    $this->assertEquals(1, $all['test_key1']);
    $this->assertEquals(2, $all['test_key2']);
  }

  function testItCanSetAtTopLevel() {
    $this->controller->set('test_key', 1);
    $this->assertEquals(1, $this->controller->get('test_key'));
    $db_value = Setting::getValue('test_key');
    $this->assertEquals(1, $db_value);
  }

  function testItCanSetAtNestedLevel() {
    $this->controller->set('test_key.key1.key2', 1);
    $this->assertEquals(1, $this->controller->get('test_key.key1.key2'));
    $db_value = Setting::getValue('test_key');
    $this->assertEquals(1, $db_value['key1']['key2']);
  }

  function testItCanSetNUll() {
    $this->controller->set('test_key.key1.key2', 1);
    $this->assertEquals(1, $this->controller->get('test_key.key1.key2'));
    $this->controller->set('test_key.key1.key2', null);
    $this->assertNull(null, $this->controller->get('test_key.key1.key2'));
    $db_value = Setting::getValue('test_key');
    $this->assertNull($db_value['key1']['key2']);
  }

  function testItCanOverrideValueAndSetAtNestedLevel() {
    $this->controller->set('test_key.key1', 1);
    $this->controller->set('test_key.key1.key2', 1);
    $this->assertEquals(1, $this->controller->get('test_key.key1.key2'));
    $db_value = Setting::getValue('test_key');
    $this->assertEquals(1, $db_value['key1']['key2']);
  }

  function testItLoadsFromDbOnlyOnce() {
    Setting::setValue('test_key', 1);
    $this->assertEquals(1, $this->controller->get('test_key'));
    Setting::setValue('test_key', 2);
    $this->assertEquals(1, $this->controller->get('test_key'));
    $this->assertEquals(true, true);
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}
