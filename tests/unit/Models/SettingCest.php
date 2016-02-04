<?php
use MailPoet\Models\Setting;

class SettingCest {
  function _before() {
    $this->before_time = time();
    $this->data = array(
      'name'  => 'sending_method',
      'value' => 'smtp'
    );

    $setting = Setting::create();
    $setting->hydrate($this->data);
    $this->saved = $setting->save();
  }

  function itCanBeCreated() {
    expect($this->saved->id() > 0)->true();
    expect($this->saved->getErrors())->false();
  }

  function itHasToBeValid() {
    $invalid_setting = Setting::create();
    $result = $invalid_setting->save();
    $errors = $result->getErrors();

    expect(is_array($errors))->true();
    expect($errors[0])->equals('You need to specify a name.');
  }

  function itHasACreatedAtOnCreation() {
    $setting = Setting::where('name', $this->data['name'])
      ->findOne();
    $time_difference = strtotime($setting->created_at) >= $this->before_time;
    expect($time_difference)->equals(true);
  }

  function itHasAnUpdatedAtOnCreation() {
    $setting = Setting::where('name', $this->data['name'])
      ->findOne();
    $time_difference = strtotime($setting->updated_at) >= $this->before_time;
    expect($time_difference)->equals(true);
  }

  function itKeepsTheCreatedAtOnUpdate() {
    $setting = Setting::where('name', $this->data['name'])
      ->findOne();
    $old_created_at = $setting->created_at;
    $setting->value = 'http_api';
    $setting->save();
    expect($old_created_at)->equals($setting->created_at);
  }

  function itUpdatesTheUpdatedAtOnUpdate() {
    $setting = Setting::where('name', $this->data['name'])
      ->findOne();
    $update_time = time();
    $setting->value = 'http_api';
    $setting->save();
    $time_difference = strtotime($setting->updated_at) >= $update_time;
    expect($time_difference)->equals(true);
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
    ORM::forTable(Setting::$_table)
      ->deleteMany();
  }
}
