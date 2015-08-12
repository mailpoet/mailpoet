<?php
use \MailPoet\Models\Setting;

class SettingCest {

  function _before() {
    $this->before_time = time();
    $setting = Setting::create();
    $setting->name = 'sending_method';
    $setting->value = 'smtp';
    $setting->save();
  }

  function itCanBeCreated() {
    $setting = Setting::where('name', 'sending_method')->findOne();
    expect($setting->id)->notNull();
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
    Setting::where('name', 'sending_method')->findOne()->delete();
  }
}
