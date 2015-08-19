<?php

use MailPoet\Models\Setting as SettingsModel;
use MailPoet\Router\Settings as SettingsRouter;

class SettingsCest {

  function _before() {
    for ($count = 1; $count <= 5; $count++) {
      $data = array(
        'name' => 'name' . $count,
        'value' => 'value' . $count
      );
      $setting = SettingsModel::create();
      $setting->hydrate($data);
      $setting->save();
    }
  }

  function itCanGetASingleSetting() {
    $router = new SettingsRouter();
    $params = array('name' => 'name5');
    $setting = json_decode($router->get($params), true);
    expect($setting[0]['value'])->equals('value5');
  }

  function itCanGetMultupleSettings() {
    $router = new SettingsRouter();
    $params = array(
      array('name' => 'name2'),
      array('name' => 'name5')
    );
    $setting = json_decode($router->get($params), true);
    expect($setting[0]['value'])->equals('value2');
    expect($setting[1]['value'])->equals('value5');
  }

  function itReturnsAnErrorOnInvalidParameters() {
    $router = new SettingsRouter();
    $params = array('somename' => 'somevalue');
    $setting = json_decode($router->get($params), true);
    expect($setting['error'])->equals('invalid_params');
  }

  function _after() {
    $settings = ORM::for_table(SettingsModel::$_table)
      ->delete_many();
  }

}