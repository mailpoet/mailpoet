<?php
use \MailPoet\Models\Setting;

class SettingCest {

    function _before() {
      $hello_setting = Setting::where('name', 'Hello')->findOne();
      if (!empty($hello_setting)) {
        $hello_setting->delete();
      }
      $setting = Setting::where('name', 'sending_method')->findOne();
      if (empty($setting)) {
        $setting = Setting::create();
        $setting->name = 'sending_method';
        $setting->value = 'smtp';
        $setting->save();
      }
    }

    function itCanBeCreated() {
      $setting = Setting::where('name', 'sending_method')->findOne();
      expect($setting->id)->notNull();
    }


    function _after() {
      $setting = Setting::where('name', 'sending_method')->findOne()->delete();
    }

    function itHasTimestampsOnCreation () {
      $to_create = Setting::create();
      $to_create->name = 'Hello';
      $to_create->value = 'World';
      $beforeCreate = time();
      $to_create->save();
      $created = Setting::where('name', 'Hello')->findOne();
      expect(is_string($created->created_at))->equals(true);
      expect(strtotime($created->created_at) >= $beforeCreate)->equals(true);
      $created->delete();
    }

    function itUpdatesTimestampsOnUpdate () {
      $created = Setting::create();
      $created->name = 'Hello';
      $created->value = 'World';
      $created->save();
      $created->value = 'World!';
      $beforeUpdate = time();
      $created->save();
      $updated = Setting::where('name', 'Hello')->findOne();
      expect(is_string($updated->updated_at))->equals(true);
      expect(strtotime($updated->updated_at) >= $beforeUpdate)->equals(true);
      $updated->delete();
    }

}
