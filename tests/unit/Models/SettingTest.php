<?php
use MailPoet\Models\Setting;

class SettingTest extends MailPoetTest {
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

  function testItHasDefaultSettings() {
    $default_settings = Setting::getDefaults();
    expect($default_settings)->notEmpty();
    expect($default_settings['signup_confirmation']['enabled'])->true();
  }

  function testItCanLoadDefaults() {
    Setting::$defaults = null;
    expect(Setting::$defaults)->null();

    $default_settings = Setting::getDefaults();
    expect(Setting::$defaults)->notEmpty();
    expect($default_settings['signup_confirmation']['enabled'])->true();
  }

  function testItCanGetAllSettingsIncludingDefaults() {
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

    // default settings
    $default_settings = Setting::getDefaults();
    expect($settings['signup_confirmation'])
      ->equals($default_settings['signup_confirmation']);
  }

  function testItCanSetAndGetValues() {
    // try to get an "unknown" key
    $setting = Setting::getValue('unknown_key', 'default_value');
    expect($setting)->equals('default_value');

    // setting a "known" key
    $setting = Setting::setValue('known_key', 'actual_value');
    expect($setting)->equals(true);

    // try to get a "known" key
    $setting = Setting::getValue('known_key', 'default_value');
    expect($setting)->equals('actual_value');

    // try to get an "unknown" subkey of a "known" key
    $setting = Setting::getValue('known_key.unknown_subkey', 'default_value');
    expect($setting)->equals('default_value');

    // try to get an "unknown" subkey of an "unknown" key
    $setting = Setting::getValue('unknown_key.unknown_subkey', 'default_value');
    expect($setting)->equals('default_value');
  }

  function testItShouldReturnDefaultsSetInModelIfNotSet() {
    // model specified default settings
    $default_settings = Setting::getDefaults();

    // try to get the MTA settings (which don't exist in the database)
    $mta_settings = Setting::getValue('mta');
    expect($mta_settings)->equals($default_settings['mta']);
  }

  function testItShouldReturnCustomDefaultsInsteadOfDefaultsSetInModel() {
    // try to get the MTA settings (which don't exist in the database)
    // but specify a custom default value
    $custom_mta_settings = Setting::getValue('mta', array(
      'custom_default' => 'value'
    ));
    expect($custom_mta_settings)->equals(array(
      'custom_default' => 'value'
    ));
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
    expect(Setting::setValue('test', '123'))->true();
    expect(Setting::getValue('test'))->equals('123');
  }

  function testItCanGetAndSetNestedValue() {
    expect(Setting::setValue('test.key', '123'))->true();
    expect(Setting::getValue('test.key'))->equals('123');

    expect(Setting::setValue('test.key.subkey', '123'))->true();
    expect(Setting::setValue('test.key.subkey2', '456'))->true();

    expect(Setting::getValue('test.key'))->notEmpty();
    expect(Setting::getValue('test.key.subkey'))->equals('123');
    expect(Setting::getValue('test.key.subkey2'))->equals('456');
  }

  function testItCanSetValueToNull() {
    expect(Setting::setValue('test.key', true))->true();
    expect(Setting::getValue('test.key'))->equals(true);

    expect(Setting::setValue('test.key', null))->true();
    expect(Setting::getValue('test.key'))->null();
  }

  function _after() {
    Setting::deleteMany();
  }
}
