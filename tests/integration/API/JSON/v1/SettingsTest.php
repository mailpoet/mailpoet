<?php
namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\v1\Settings;
use MailPoet\Models\Setting;
use MailPoet\Settings\SettingsController;

class SettingsTest extends \MailPoetTest {

  /** @var Settings */
  private $endpoint;

  function _before() {
    parent::_before();
    $settings = new SettingsController();
    $settings->set('some.setting.key', true);
    $this->endpoint = new Settings($settings);
  }

  function testItCanGetSettings() {
    $response = $this->endpoint->get();
    expect($response->status)->equals(APIResponse::STATUS_OK);

    expect($response->data)->notEmpty();
    expect($response->data['some']['setting']['key'])->true();

    Setting::deleteMany();
    SettingsController::resetCache();
    $response = $this->endpoint->get();
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

    $response = $this->endpoint->set(/* missing data */);
    expect($response->errors[0]['error'])->equals(APIError::BAD_REQUEST);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);

    $response = $this->endpoint->set($new_settings);
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $response = $this->endpoint->get();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['some']['setting'])->hasntKey('key');
    expect($response->data['some']['setting']['new_key'])->true();
    expect($response->data['some']['new_setting'])->true();
  }

  function _after() {
    \ORM::forTable(Setting::$_table)->deleteMany();
  }
}
