<?php
use MailPoet\Models\Setting;

class SettingCest {

  function _before() {
    $this->data = array('name' => 'sending_method', 'value' => 'smtp');

    // clean up after previously failed test
    $setting = Setting::where('name', $this->data['name'])->findOne();
    if ($setting !== false) {
      $setting->delete();
    }

    $setting = Setting::create();
    $setting->name = $this->data['name'];
    $setting->value = $this->data['value'];
    $setting->save();
  }

  function itCanBeCreated() {
    $setting = Setting::where('name', $this->data['name'])->findOne();
    expect($setting->id)->notNull();
  }

  function nameShouldValidate() {
    $conflict_setting = Setting::create();
    $conflict_setting->validateField('name', '');
    expect($conflict_setting->getValidationErrors()[0])->equals('validation_option_name_blank');

    $conflict_setting = Setting::create();
    $conflict_setting->validateField('name', 31337);
    expect($conflict_setting->getValidationErrors()[0])->equals('validation_option_name_string');

    $conflict_setting = Setting::create();
    $conflict_setting->validateField('name', 'a');
    expect($conflict_setting->getValidationErrors()[0])->equals('validation_option_name_length');
  }

  function valueShouldValidate() {
    $conflict_setting = Setting::create();
    $conflict_setting->validateField('value', '');
    expect($conflict_setting->getValidationErrors()[0])->equals('validation_option_value_blank');

    $conflict_setting = Setting::create();
    $conflict_setting->validateField('value', 31337);
    expect($conflict_setting->getValidationErrors()[0])->equals('validation_option_value_string');

    $conflict_setting = Setting::create();
    $conflict_setting->validateField('value', 'a');
    expect($conflict_setting->getValidationErrors()[0])->equals('validation_option_value_length');
  }

  function _after() {
    $setting = Setting::where('name', $this->data['name'])->findOne()->delete();
  }

}
