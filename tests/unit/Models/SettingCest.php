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
    $this->result = $setting->save();
  }

  function itCanBeCreated() {
   expect($this->result)->equals(true);
  }

  function itHasToBeValid() {
    expect($this->result)->equals(true);
    $empty_model = Setting::create();
    expect($empty_model->save())->notEquals(true);
    $validations = $empty_model->getValidationErrors();
    expect(count($validations))->equals(2);
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

    $result = Setting::createOrUpdate($data);
    expect($result)->equals(true);
    $record = Setting::where('name', $data['name'])
      ->find_one();
    expect($record->value)->equals($data['value']);

    $data['value'] = 'new data';
    $result = Setting::createOrUpdate($data);
    expect($result)->equals(true);
    $record = Setting::where('name', $data['name'])
      ->find_one();
    expect($record->value)->equals('new data');
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
