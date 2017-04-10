<?php
use MailPoet\API\Response as APIResponse;
use MailPoet\API\Error as APIError;
use MailPoet\API\Endpoints\v1\Settings;
use MailPoet\Models\Setting;

class SettingsTest extends MailPoetTest {
  function _before() {
    Setting::setValue('some.setting.key', true);
  }

  function testItCanGetSettings() {
    $router = new Settings();

    $response = $router->get();
    expect($response->status)->equals(APIResponse::STATUS_OK);

    expect($response->data)->notEmpty();
    expect($response->data['some']['setting']['key'])->true();

    Setting::deleteMany();
    $response = $router->get();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(Setting::getDefaults());
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
    expect($response->errors[0]['error'])->equals(APIError::BAD_REQUEST);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);

    $response = $router->set($new_settings);
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $response = $router->get();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['some']['setting'])->hasntKey('key');
    expect($response->data['some']['setting']['new_key'])->true();
    expect($response->data['some']['new_setting'])->true();
  }

  function _after() {
    ORM::forTable(Setting::$_table)->deleteMany();
  }
}