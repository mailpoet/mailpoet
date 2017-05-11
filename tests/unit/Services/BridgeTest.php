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

    $this->expiring_premium_key = 'expiring' . $this->valid_key;
    $this->used_premium_key = '402' . $this->valid_key;

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
    expect($this->getMSSKey())->equals($this->valid_key);
  }

  function testItChecksInvalidMSSKey() {
    $result = $this->bridge->checkMSSKey($this->invalid_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::MAILPOET_KEY_INVALID);
    expect($this->getMSSKey())->equals($this->invalid_key);
  }

  function testItChecksExpiingMSSKey() {
    $result = $this->bridge->checkMSSKey($this->expiring_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::MAILPOET_KEY_EXPIRING);
    expect($result['data']['expire_at'])->notEmpty();
    expect($this->getMSSKey())->equals($this->expiring_key);
  }

  function testItReturnsErrorStateOnEmptyAPIResponseCodeDuringMSSCheck() {
    $api = Stub::make(new API(null), array('checkMSSKey' => array()), $this);
    $this->bridge->api = $api;
    $result = $this->bridge->checkMSSKey($this->valid_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::MAILPOET_KEY_CHECK_ERROR);
    expect($this->getMSSKey())->notEquals($this->valid_key);
  }

  function testItChecksValidPremiumKey() {
    $result = $this->bridge->checkPremiumKey($this->valid_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::PREMIUM_KEY_VALID);
    expect($this->getPremiumKey())->equals($this->valid_key);
  }

  function testItChecksInvalidPremiumKey() {
    $result = $this->bridge->checkPremiumKey($this->invalid_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::PREMIUM_KEY_INVALID);
    expect($this->getPremiumKey())->equals($this->invalid_key);
  }

  function testItChecksAlreadyUsedPremiumKey() {
    $result = $this->bridge->checkPremiumKey($this->used_premium_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::PREMIUM_KEY_ALREADY_USED);
    expect($this->getPremiumKey())->equals($this->used_premium_key);
  }

  function testItChecksExpiringPremiumKey() {
    $result = $this->bridge->checkPremiumKey($this->expiring_premium_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::PREMIUM_KEY_EXPIRING);
    expect($result['data']['expire_at'])->notEmpty();
    expect($this->getPremiumKey())->equals($this->expiring_premium_key);
  }

  function testItReturnsErrorStateOnEmptyAPIResponseCodeDuringPremiumCheck() {
    $api = Stub::make(new API(null), array('checkPremiumKey' => array()), $this);
    $this->bridge->api = $api;
    $result = $this->bridge->checkPremiumKey($this->valid_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::PREMIUM_KEY_CHECK_ERROR);
    expect($this->getPremiumKey())->notEquals($this->valid_key);
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
    $value = Setting::getValue(Bridge::API_KEY_STATE_SETTING_NAME);
    expect($value)->equals(array('state' => Bridge::MAILPOET_KEY_INVALID));
  }

  function testItChecksKeysOnSettingsSave() {
    $api = Stub::make(
      new API(null),
      array(
        'checkMSSKey' => Stub::once(function() { return array(); }),
        'checkPremiumKey' => Stub::once(function() { return array(); })
      ),
      $this
    );
    $this->bridge->api = $api;

    $settings = array();
    $settings[Mailer::MAILER_CONFIG_SETTING_NAME]['mailpoet_api_key'] = $this->valid_key;
    $settings['premium']['premium_key'] = $this->valid_key;

    $this->setMailPoetSendingMethod();
    $this->bridge->onSettingsSave($settings);
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

  private function fillPremiumKey() {
    Setting::setValue(
      Bridge::PREMIUM_KEY_SETTING_NAME,
      '123457890abcdef'
    );
  }

  private function getPremiumKey() {
    return Setting::getValue(Bridge::PREMIUM_KEY_SETTING_NAME);
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}