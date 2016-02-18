<?php
use MailPoet\Models\Setting;

class SettingCest {
  function itCanBeCreated() {
    $setting = Setting::createOrUpdate(array(
      'name' => 'key',
      'value' => 'val'
    ));
    expect($setting->id() > 0)->true();
    expect($setting->getErrors())->false();
  }

  function itHasToBeValid() {
    $invalid_setting = Setting::createOrUpdate();
    $errors = $invalid_setting->getErrors();

    expect($errors)->notEmpty();
    expect($errors[0])->equals('You need to specify a name.');
  }

  function itCanGetAllSettings() {
    Setting::setValue('key_1', 'value_1');
    Setting::setValue('key_2', 'value_2');
    Setting::setValue('key_3', array(
      'subkey_1' => 'subvalue_1',
      'subkey_2' => 'subvalue_2'
    ));

    $settings = Setting::getAll();
    expect(array_keys($settings))->count(3);
    expect($settings['key_1'])->equals('value_1');
    expect($settings['key_2'])->equals('value_2');
    expect($settings['key_3'])->equals(array(
      'subkey_1' => 'subvalue_1',
      'subkey_2' => 'subvalue_2'
    ));
  }

  function itReturnsDefaultValueIfNotSet() {
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

  function itCanCreateOrUpdate() {
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

  function itCanGetAndSetValue() {
    expect(Setting::setValue('test', '123'))->true();
    expect(Setting::getValue('test'))->equals('123');
  }

  function itCanGetAndSetNestedValue() {
    expect(Setting::setValue('test.key', '123'))->true();
    expect(Setting::getValue('test.key'))->equals('123');

    expect(Setting::setValue('test.key.subkey', '123'))->true();
    expect(Setting::setValue('test.key.subkey2', '456'))->true();

    expect(Setting::getValue('test.key'))->notEmpty();
    expect(Setting::getValue('test.key.subkey'))->equals('123');
    expect(Setting::getValue('test.key.subkey2'))->equals('456');
  }

  function itCanSetValueToNull() {
    expect(Setting::setValue('test.key', true))->true();
    expect(Setting::getValue('test.key'))->equals(true);

    expect(Setting::setValue('test.key', null))->true();
    expect(Setting::getValue('test.key'))->null();
  }

  function _after() {
    Setting::deleteMany();
  }
}
