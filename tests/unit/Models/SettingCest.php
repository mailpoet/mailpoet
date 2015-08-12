<?php
use MailPoet\Models\Setting;

class SettingCest {

  function _before() {
    $this->data = array(
        'name'  => 'sending_method',
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

  function _after() {
    $setting = Setting::where('name', $this->data['name'])
                      ->findOne()
                      ->delete();
  }

}
