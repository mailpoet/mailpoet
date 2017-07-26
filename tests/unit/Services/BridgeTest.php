<?php

use Codeception\Util\Stub;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\API;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Setting;

require_once('BridgeTestMockAPI.php');
use MailPoet\Services\Bridge\BridgeTestMockAPI as MockAPI;

class BridgeTest extends MailPoetTest {
  function _before() {
    $this->valid_key = 'abcdefghijklmnopqrstuvwxyz';
    $this->invalid_key = '401' . $this->valid_key;
    $this->expiring_key = '402' . $this->valid_key;
    $this->uncheckable_key = '503' . $this->valid_key;

    $this->expiring_premium_key = 'expiring' . $this->valid_key;
    $this->used_premium_key = '402' . $this->valid_key;

    $this->bridge = new Bridge();

    $this->bridge->api = new MockAPI('key');
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

  function testItChecksIfPremiumKeyIsSpecified() {
    expect(Bridge::isPremiumKeySpecified())->false();
    $this->fillPremiumKey();
    expect(Bridge::isPremiumKeySpecified())->true();
  }

  function testItInstantiatesDefaultAPI() {
    $this->bridge->api = null;
    $this->bridge->initApi(null);
    expect($this->bridge->api instanceof API)->true();
  }

  function testItChecksValidMSSKey() {
    $result = $this->bridge->checkMSSKey($this->valid_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::MAILPOET_KEY_VALID);
  }

  function testItChecksInvalidMSSKey() {
    $result = $this->bridge->checkMSSKey($this->invalid_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::MAILPOET_KEY_INVALID);
  }

  function testItChecksExpiringMSSKey() {
    $result = $this->bridge->checkMSSKey($this->expiring_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::MAILPOET_KEY_EXPIRING);
    expect($result['data']['expire_at'])->notEmpty();
  }

  function testItReturnsErrorStateOnEmptyAPIResponseCodeDuringMSSCheck() {
    $api = Stub::make(new API(null), array('checkMSSKey' => array()), $this);
    $this->bridge->api = $api;
    $result = $this->bridge->checkMSSKey($this->valid_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::MAILPOET_KEY_CHECK_ERROR);
  }

  function testItStoresExpectedMSSKeyStates() {
    $states = array(
      Bridge::MAILPOET_KEY_VALID => $this->valid_key,
      Bridge::MAILPOET_KEY_INVALID => $this->invalid_key,
      Bridge::MAILPOET_KEY_EXPIRING => $this->expiring_key
    );
    foreach($states as $state => $key) {
      $state = array('state' => $state);
      $this->bridge->storeMSSKeyAndState($key, $state);
      expect($this->getMSSKey())->equals($key);
      expect($this->getMSSKeyState())->equals($state);
    }
  }

  function testItDoesNotStoreErroneousOrUnexpectedMSSKeyStates() {
    $states = array(
      array('state' => Bridge::MAILPOET_KEY_CHECK_ERROR),
      array()
    );
    foreach($states as $state) {
      $this->bridge->storeMSSKeyAndState($this->valid_key, $state);
      expect($this->getMSSKey())->notEquals($this->valid_key);
      expect($this->getMSSKeyState())->notEquals($state);
    }
  }

  function testItChecksValidPremiumKey() {
    $result = $this->bridge->checkPremiumKey($this->valid_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::PREMIUM_KEY_VALID);
  }

  function testItChecksInvalidPremiumKey() {
    $result = $this->bridge->checkPremiumKey($this->invalid_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::PREMIUM_KEY_INVALID);
  }

  function testItChecksAlreadyUsedPremiumKey() {
    $result = $this->bridge->checkPremiumKey($this->used_premium_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::PREMIUM_KEY_ALREADY_USED);
  }

  function testItChecksExpiringPremiumKey() {
    $result = $this->bridge->checkPremiumKey($this->expiring_premium_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::PREMIUM_KEY_EXPIRING);
    expect($result['data']['expire_at'])->notEmpty();
  }

  function testItReturnsErrorStateOnEmptyAPIResponseCodeDuringPremiumCheck() {
    $api = Stub::make(new API(null), array('checkPremiumKey' => array()), $this);
    $this->bridge->api = $api;
    $result = $this->bridge->checkPremiumKey($this->valid_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::PREMIUM_KEY_CHECK_ERROR);
  }

  function testItStoresExpectedPremiumKeyStates() {
    $states = array(
      Bridge::PREMIUM_KEY_VALID => $this->valid_key,
      Bridge::PREMIUM_KEY_INVALID => $this->invalid_key,
      Bridge::PREMIUM_KEY_ALREADY_USED => $this->used_premium_key,
      Bridge::PREMIUM_KEY_EXPIRING => $this->expiring_key
    );
    foreach($states as $state => $key) {
      $state = array('state' => $state);
      $this->bridge->storePremiumKeyAndState($key, $state);
      expect($this->getPremiumKey())->equals($key);
      expect($this->getPremiumKeyState())->equals($state);
    }
  }

  function testItDoesNotStoreErroneousOrUnexpectedPremiumKeyStates() {
    $states = array(
      array('state' => Bridge::PREMIUM_KEY_CHECK_ERROR),
      array()
    );
    foreach($states as $state) {
      $this->bridge->storePremiumKeyAndState($this->valid_key, $state);
      expect($this->getPremiumKey())->notEquals($this->valid_key);
      expect($this->getPremiumKeyState())->notEquals($state);
    }
  }

  function testItUpdatesSubscriberCount() {
    // it performs update if the key is valid or expiring
    $result = array();
    $result['state'] = Bridge::MAILPOET_KEY_VALID;
    $updated = $this->bridge->updateSubscriberCount($result);
    expect($updated)->true();
    $result['state'] = Bridge::MAILPOET_KEY_EXPIRING;
    $updated = $this->bridge->updateSubscriberCount($result);
    expect($updated)->true();
    // it does not perform update if the key is invalid
    $result['state'] = Bridge::MAILPOET_KEY_INVALID;
    $updated = $this->bridge->updateSubscriberCount($result);
    expect($updated)->null();
  }

  function testItInvalidatesMSSKey() {
    Setting::setValue(
      Bridge::API_KEY_STATE_SETTING_NAME,
      array('state' => Bridge::MAILPOET_KEY_VALID)
    );
    Bridge::invalidateKey();
    expect($this->getMSSKeyState())->equals(array('state' => Bridge::MAILPOET_KEY_INVALID));
  }

  function testItChecksAndStoresKeysOnSettingsSave() {
    $response = array('abc' => 'def');
    $bridge = Stub::makeEmptyExcept(
      $this->bridge,
      'onSettingsSave',
      array(
        'checkMSSKey' => $response,
        'checkPremiumKey' => $response
      ),
      $this
    );
    $bridge->expects($this->once())
      ->method('checkMSSKey')
      ->with($this->equalTo($this->valid_key));
    $bridge->expects($this->once())
      ->method('storeMSSKeyAndState')
      ->with(
        $this->equalTo($this->valid_key),
        $this->equalTo($response)
      );
    $bridge->expects($this->once())
      ->method('updateSubscriberCount')
      ->with($this->equalTo($response));

    $bridge->expects($this->once())
      ->method('checkPremiumKey')
      ->with($this->equalTo($this->valid_key));
    $bridge->expects($this->once())
      ->method('storePremiumKeyAndState')
      ->with(
        $this->equalTo($this->valid_key),
        $this->equalTo($response)
      );

    $settings = array();
    $settings[Mailer::MAILER_CONFIG_SETTING_NAME]['mailpoet_api_key'] = $this->valid_key;
    $settings['premium']['premium_key'] = $this->valid_key;

    $this->setMailPoetSendingMethod();
    $bridge->onSettingsSave($settings);
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

  private function getMSSKey() {
    return Setting::getValue(Bridge::API_KEY_SETTING_NAME);
  }

  private function getMSSKeyState() {
    return Setting::getValue(Bridge::API_KEY_STATE_SETTING_NAME);
  }

  private function fillPremiumKey() {
    Setting::setValue(
      Bridge::PREMIUM_KEY_SETTING_NAME,
      '123457890abcdef'
    );
  }

  private function getPremiumKey() {
    return Setting::getValue(Bridge::PREMIUM_KEY_SETTING_NAME);
  }

  private function getPremiumKeyState() {
    return Setting::getValue(Bridge::PREMIUM_KEY_STATE_SETTING_NAME);
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}