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

  function itReturnsAnErrorOnGetInvalidParameters() {
    $router = new SettingsRouter();
    $params = array('somename' => 'somevalue');
    $setting = json_decode($router->get($params), true);
    expect($setting['error'])->equals('invalid_params');
  }

  function itCanUpdateASingleSetting() {
    $router = new SettingsRouter();
    $params = array(
      'name' => 'name1',
      'value' => 'newvalue1'
    );
    $router->set($params);
    $updatedSetting = SettingsModel::where('name', 'name1')
      ->find_one();
    expect($updatedSetting->value)->equals('newvalue1');
  }

  function itCanUpdateMultipleSettings() {
    $router = new SettingsRouter();
    $params = array(
      array(
        'name' => 'name1',
        'value' => 'newvalue1'
      ),
      array(
        'name' => 'name2',
        'value' => 'newvalue2'
      ),
    );
    $router->set($params);
    $updatedSettings = SettingsModel::where_in('name', array(
      'name1',
      'name2'
    ))
      ->find_array();
    expect($updatedSettings[0]['value'])->equals('newvalue1');
    expect($updatedSettings[1]['value'])->equals('newvalue2');
  }

  function itCanCreateASingleSetting() {
    $router = new SettingsRouter();
    $params = array(
      'name' => 'name10',
      'value' => 'newvalue10'
    );
    $router->set($params);
    $newSetting = SettingsModel::where('name', 'name10')
      ->find_one();
    expect($newSetting->value)->equals('newvalue10');
  }

  function itCanCreateMultipleSettings() {
    $router = new SettingsRouter();
    $params = array(
      array(
        'name' => 'name10',
        'value' => 'newvalue10'
      ),
      array(
        'name' => 'name11',
        'value' => 'newvalue11'
      ),
    );
    $router->set($params);
    $newSettings = SettingsModel::where_in('name', array(
      'name10',
      'name11'
    ))
      ->find_array();
    expect(count($newSettings))->equals(2);
  }

  function itCanCreateNewAndUpdateExistingSetting() {
    $router = new SettingsRouter();
    $params = array(
      array(
        'name' => 'name1',
        'value' => 'newvalue1'
      ),
      array(
        'name' => 'name10',
        'value' => 'value10'
      ),
    );
    $router->set($params);
    $newOrUpdatedSettings = SettingsModel::where_in('name', array(
      'name1',
      'name10'
    ))
      ->find_array();
    expect($newOrUpdatedSettings[0]['value'])->equals('newvalue1');
    expect($newOrUpdatedSettings[1]['value'])->equals('value10');
  }

  function itReturnsAnErrorOnSetInvalidParameters() {
    $router = new SettingsRouter();
    $params = array('somename' => 'somevalue');
    $setting = json_decode($router->set($params), true);
    expect($setting['error'])->equals('invalid_params');
  }

  function _after() {
    $deleteSettings = ORM::for_table(SettingsModel::$_table)
      ->delete_many();
  }

}