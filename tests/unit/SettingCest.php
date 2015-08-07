<?php
use \UnitTester;
use \MailPoet\Models\Setting;

class SettingCest {

    function _before() {
      $setting = Setting::create();
      $setting->name = 'sending_method';
      $setting->value = 'smtp';
      $setting->save();
    }

    function itCanBeCreated() {
      $setting = Setting::where('name', 'sending_method')->findOne();
      expect($setting->id)->notNull();
    }


    function _after() {
      $setting = Setting::where('name', 'sending_method')->findOne()->delete();
    }
}
