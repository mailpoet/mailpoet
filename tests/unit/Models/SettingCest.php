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
    $setting->save();
  }

  function itCanBeCreated() {
    $setting = Setting::where('name', $this->data['name'])->findOne();
    expect($setting->id)->notNull();
  }

  function nameShouldValidate() {
    $invalid_setting = Setting::create();
    $invalid_setting->validateField('name', '');
    expect($invalid_setting->getValidationErrors()[0])->equals('name_is_blank');

    $invalid_setting = Setting::create();
    $invalid_setting->validateField('name', 31337);
    expect($invalid_setting->getValidationErrors()[0])->equals('name_is_not_string');

    $invalid_setting = Setting::create();
    $invalid_setting->validateField('name', 'a');
    expect($invalid_setting->getValidationErrors()[0])->equals('name_is_short');
  }

  function valueShouldValidate() {
    $invalid_setting = Setting::create();
    $invalid_setting->validateField('value', '');
    expect($invalid_setting->getValidationErrors()[0])->equals('value_is_blank');

    $invalid_setting = Setting::create();
    $invalid_setting->validateField('value', 31337);
    expect($invalid_setting->getValidationErrors()[0])->equals('value_is_not_string');

    $invalid_setting = Setting::create();
    $invalid_setting->validateField('value', 'a');
    expect($invalid_setting->getValidationErrors()[0])->equals('value_is_short');
  }

  function itHasACreatedAtOnCreation() {
    $setting = Setting::where('name', 'sending_method')->findOne();
    $time_difference = strtotime($setting->created_at) >= $this->before_time;
    expect($time_difference)->equals(true);
  }

  function itHasAnUpdatedAtOnCreation() {
    $setting = Setting::where('name', 'sending_method')->findOne();
    $time_difference = strtotime($setting->updated_at) >= $this->before_time;
    expect($time_difference)->equals(true);
  }

  function itKeepsTheCreatedAtOnUpdate() {
    $setting = Setting::where('name', 'sending_method')->findOne();
    $old_created_at = $setting->created_at;
    $setting->value = 'http_api';
    $setting->save();
    expect($old_created_at)->equals($setting->created_at);
  }

  function itUpdatesTheUpdatedAtOnUpdate() {
    $setting = Setting::where('name', 'sending_method')->findOne();
    $update_time = time();
    $setting->value = 'http_api';
    $setting->save();
    $time_difference = strtotime($setting->updated_at) >= $update_time;
    expect($time_difference)->equals(true);
  }

  function _after() {
    $setting = Setting::where('name', $this->data['name'])->findOne()->delete();
  }
}
