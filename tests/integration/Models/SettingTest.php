<?php
namespace MailPoet\Test\Models;

use MailPoet\Models\Setting;
use MailPoet\Settings\SettingsController;

class SettingTest extends \MailPoetTest {
  function testItCanBeCreated() {
    $setting = Setting::createOrUpdate(array(
      'name' => 'key',
      'value' => 'val'
    ));
    expect($setting->id() > 0)->true();
    expect($setting->getErrors())->false();
  }

  function testItHasToBeValid() {
    $invalid_setting = Setting::createOrUpdate();
    $errors = $invalid_setting->getErrors();

    expect($errors)->notEmpty();
    expect($errors[0])->equals('Please specify a name.');
  }

  function testItCanGetAllSettings() {
    Setting::setValue('key_1', 'value_1');
    Setting::setValue('key_2', 'value_2');
    Setting::setValue('key_3', array(
      'subkey_1' => 'subvalue_1',
      'subkey_2' => 'subvalue_2'
    ));

    $settings = Setting::getAll();
    expect($settings['key_1'])->equals('value_1');
    expect($settings['key_2'])->equals('value_2');
    expect($settings['key_3'])->equals(array(
      'subkey_1' => 'subvalue_1',
      'subkey_2' => 'subvalue_2'
    ));
  }

  function testItCanSetAndGetValues() {
    // try to get an "unknown" key
    $setting = Setting::getValue('unknown_key');
    expect($setting)->equals(null);

    // setting a "known" key
    $setting = Setting::setValue('known_key', '  actual_value  ');
    expect($setting)->equals(true);

    // try to get a "known" key
    $setting = Setting::getValue('known_key', 'default_value');
    expect($setting)->equals('actual_value');
  }

  function testItCanCreateOrUpdate() {
    $data = array(
      'name'  => 'new',
      'value' => 'data'
    );

    $created_setting = Setting::createOrUpdate($data);
    expect($created_setting->id() > 0)->true();
    expect($created_setting->getErrors())->false();

    $setting = Setting::where('name', $data['name'])->findOne();
    expect($setting->value)->equals($data['value']);

    $data['value'] = 'new data';
    $updated_setting = Setting::createOrUpdate($data);
    expect($updated_setting->id() > 0)->true();
    expect($updated_setting->getErrors())->false();

    $setting = Setting::where('name', $data['name'])->findOne();
    expect($setting->value)->equals('new data');
  }

  function testItCanGetAndSetValue() {
    expect(Setting::setValue('test', '  123  '))->true();
    expect(Setting::getValue('test'))->equals('123');
  }

  function testItCanSetValueToNull() {
    expect(Setting::setValue('test_key', true))->true();
    expect(Setting::getValue('test_key'))->equals(true);

    expect(Setting::setValue('test_key', null))->true();
    expect(Setting::getValue('test_key'))->null();
  }

  function testSaveDefaultSenderIfNeededNotSaveEmptyValue() {
    $settings_controller = new SettingsController();
    Setting::saveDefaultSenderIfNeeded('', null);
    expect($settings_controller->get('sender'))->null();
  }

  function testSaveDefaultSenderIfNeededDoesntOverride() {
    $settings_controller = new SettingsController();
    $settings_controller->set('sender', array('name' => 'sender1', 'address' => 'sender1address'));
    Setting::saveDefaultSenderIfNeeded('sender2address', 'sender1');
    $settings = $settings_controller->get('sender');
    expect($settings['name'])->equals('sender1');
    expect($settings['address'])->equals('sender1address');
  }

  function testSaveDefaultSenderIfNeeded() {
    $settings_controller = new SettingsController();
    Setting::saveDefaultSenderIfNeeded('senderAddress', 'sender');
    $settings = $settings_controller->get('sender');
    expect($settings['name'])->equals('sender');
    expect($settings['address'])->equals('senderAddress');
  }

  function _after() {
    Setting::deleteMany();
  }
}
