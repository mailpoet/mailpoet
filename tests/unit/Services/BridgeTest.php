<?php

use Codeception\Util\Stub;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\API;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Setting;

require_once('BridgeTestMockAPI.php');

class BridgeTest extends MailPoetTest {
  function _before() {
    $this->valid_key = 'abcdefghijklmnopqrstuvwxyz';
    $this->invalid_key = '401' . $this->valid_key;
    $this->expiring_key = '402' . $this->valid_key;
    $this->uncheckable_key = '503' . $this->valid_key;

    $this->bridge = new Bridge();

    $this->bridge->api = new MailPoet\Services\Bridge\MockAPI('key');
  }

  function testItChecksIfCurrentSendingMethodIsMailpoet() {
    expect(Bridge::isMPSendingServiceEnabled())->false();
    $this->setMailPoetSendingMethod();
    expect(Bridge::isMPSendingServiceEnabled())->true();
  }

  function testMPCheckReturnsFalseWhenMailerThrowsException() {
    Setting::setValue(Mailer::MAILER_CONFIG_SETTING_NAME, '');
    expect(Bridge::isMPSendingServiceEnabled())->false();
  }

  function testItInstantiatesDefaultAPI() {
    $this->bridge->api = null;
    $this->bridge->initApi(null);
    expect($this->bridge->api instanceof API)->true();
  }

  function testItChecksValidKey() {
    $result = $this->bridge->checkKey($this->valid_key);
    expect($result)->notEmpty();
    expect($result['code'])->equals(Bridge::MAILPOET_KEY_VALID);

    $result = $this->bridge->checkKey($this->invalid_key);
    expect($result)->notEmpty();
    expect($result['code'])->equals(Bridge::MAILPOET_KEY_INVALID);

    $result = $this->bridge->checkKey($this->expiring_key);
    expect($result)->notEmpty();
    expect($result['code'])->equals(Bridge::MAILPOET_KEY_EXPIRING);
    expect($result['data']['expire_at'])->notEmpty();
  }

  function testItReturnsFalseOnEmptyAPIResponseCode() {
    $api = Stub::make(new API(null), array('checkKey' => array()), $this);
    $this->bridge->api = $api;
    $result = $this->bridge->checkKey($this->valid_key);
    expect($result)->false();
  }

  function testItInvalidatesKey() {
    Setting::setValue(Bridge::API_KEY_STATE_SETTING_NAME, array('code' => 200));
    Bridge::invalidateKey();
    $value = Setting::getValue(Bridge::API_KEY_STATE_SETTING_NAME);
    expect($value)->equals(array('code' => 401));
  }

  private function setMailPoetSendingMethod() {
    Setting::setValue(
      Mailer::MAILER_CONFIG_SETTING_NAME,
      array(
        'method' => 'MailPoet',
        'mailpoet_api_key' => 'some_key',
      )
    );
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}