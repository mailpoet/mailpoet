<?php

namespace MailPoet\Test\Models;

use MailPoet\Models\Setting;

class SettingTest extends \MailPoetTest {
  function testItCanBeCreated() {
    $setting = Setting::createOrUpdate([
      'name' => 'key',
      'value' => 'val',
    ]);
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
    Setting::createOrUpdate(['name' => 'key_1', 'value' => 'value_1']);
    Setting::createOrUpdate(['name' => 'key_2', 'value' => 'value_2']);
    Setting::createOrUpdate(['name' => 'key_3', 'value' => serialize([
      'subkey_1' => 'subvalue_1',
      'subkey_2' => 'subvalue_2',
    ])]);

    $settings = Setting::getAll();
    expect($settings['key_1'])->equals('value_1');
    expect($settings['key_2'])->equals('value_2');
    expect($settings['key_3'])->equals([
      'subkey_1' => 'subvalue_1',
      'subkey_2' => 'subvalue_2',
    ]);
  }

  function testItCanCreateOrUpdate() {
    $data = [
      'name'  => 'new',
      'value' => 'data',
    ];

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

  function _after() {
    Setting::deleteMany();
  }
}
