<?php
use \MailPoet\Router\Settings;
use \MailPoet\Models\Setting;

class SettingsTest extends MailPoetTest {
  function _before() {
    Setting::setValue('some.setting.key', true);
  }

  function testItCanGetSettings() {
    $router = new Settings();

    $settings = $router->get();
    expect($settings)->notEmpty();
    expect($settings['some']['setting']['key'])->true();

    Setting::deleteMany();
    $settings = $router->get();
    expect($settings)->equals(Setting::getDefaults());
  }

  function testItCanSetSettings() {
    $new_settings = array(
      'some' => array(
        'setting' => array(
          'new_key' => true
        ),
        'new_setting' => true
      )
    );

    $router = new Settings();

    $response = $router->set(/* missing data */);
    expect($response)->false();

    $response = $router->set($new_settings);
    expect($response)->true();

    $settings = $router->get();

    expect($settings['some']['setting'])->hasntKey('key');
    expect($settings['some']['setting']['new_key'])->true();
    expect($settings['some']['new_setting'])->true();
  }

  function _after() {
    ORM::forTable(Setting::$_table)->deleteMany();
  }
}