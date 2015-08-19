<?php
use MailPoet\Models\Setting;

class SettingCest {
  
  function _before() {
    $this->before_time = time();
    $this->data = array(
      'name' => 'sending_method',
      'value' => 'smtp'
    );

    $setting = Setting::create();
    $setting->hydrate($this->data);
    $setting->save();
  }

  function itCanBeCreated() {
    $setting = Setting::where('name', $this->data['name'])
      ->findOne();
    expect($setting->id)->notNull();
  }

  function nameShouldValidate() {
    $conflict_setting = Setting::create();
    $conflict_setting->validateField('name', '');
    expect($conflict_setting->getValidationErrors()[0])->equals('name_is_blank');

    $conflict_setting = Setting::create();
    $conflict_setting->validateField('name', 31337);
    expect($conflict_setting->getValidationErrors()[0])->equals('name_is_not_string');

    $conflict_setting = Setting::create();
    $conflict_setting->validateField('name', 'a');
    expect($conflict_setting->getValidationErrors()[0])->equals('name_is_short');
  }

  function valueShouldValidate() {
    $conflict_setting = Setting::create();
    $conflict_setting->validateField('value', '');
    expect($conflict_setting->getValidationErrors()[0])->equals('value_is_blank');

    $conflict_setting = Setting::create();
    $conflict_setting->validateField('value', 31337);
    expect($conflict_setting->getValidationErrors()[0])->equals('value_is_not_string');

    $conflict_setting = Setting::create();
    $conflict_setting->validateField('value', 'a');
    expect($conflict_setting->getValidationErrors()[0])->equals('value_is_short');
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
  
  function _after() {
    $deleteSettings = ORM::for_table(Setting::$_table)
      ->delete_many();
  }

}
