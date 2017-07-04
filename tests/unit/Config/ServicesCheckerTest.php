<?php

use MailPoet\Config\ServicesChecker;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Setting;
use MailPoet\Services\Bridge;

class ServicesCheckerTest extends MailPoetTest {
  function _before() {
    $this->setMailPoetSendingMethod();
    $this->fillPremiumKey();
    $this->services_checker = new ServicesChecker();
  }

  function testItDoesNotCheckMSSKeyIfMPSendingServiceIsDisabled() {
    $this->disableMailPoetSendingMethod();
    $result = $this->services_checker->isMailPoetAPIKeyValid();
    expect($result)->null();
  }

  function testItForciblyChecksMSSKeyIfMPSendingServiceIsDisabled() {
    $this->disableMailPoetSendingMethod();
    $result = $this->services_checker->isMailPoetAPIKeyValid(false, true);
    expect($result)->false();
  }

  function testItReturnsFalseIfMSSKeyIsNotSpecified() {
    Setting::setValue(Bridge::API_KEY_SETTING_NAME, '');
    $result = $this->services_checker->isMailPoetAPIKeyValid();
    expect($result)->false();
  }

  function testItReturnsTrueIfMSSKeyIsValid() {
    Setting::setValue(
      Bridge::API_KEY_STATE_SETTING_NAME,
      array('state' => Bridge::MAILPOET_KEY_VALID)
    );
    $result = $this->services_checker->isMailPoetAPIKeyValid();
    expect($result)->true();
  }

  function testItReturnsFalseIfMSSKeyIsInvalid() {
    Setting::setValue(
      Bridge::API_KEY_STATE_SETTING_NAME,
      array('state' => Bridge::MAILPOET_KEY_INVALID)
    );
    $result = $this->services_checker->isMailPoetAPIKeyValid();
    expect($result)->false();
  }

  function testItReturnsTrueIfMSSKeyIsExpiring() {
    Setting::setValue(
      Bridge::API_KEY_STATE_SETTING_NAME,
      array(
        'state' => Bridge::MAILPOET_KEY_EXPIRING,
        'data' => array('expire_at' => date('c'))
      )
    );
    $result = $this->services_checker->isMailPoetAPIKeyValid();
    expect($result)->true();
  }

  function testItReturnsFalseIfMSSKeyStateIsUnexpected() {
    Setting::setValue(
      Bridge::API_KEY_STATE_SETTING_NAME,
      array(
        'state' => 'unexpected'
      )
    );
    $result = $this->services_checker->isMailPoetAPIKeyValid();
    expect($result)->false();
  }

  function testItReturnsFalseIfMSSKeyStateIsEmpty() {
    Setting::setValue(
      Bridge::API_KEY_STATE_SETTING_NAME,
      array(
        'state' => ''
      )
    );
    $result = $this->services_checker->isMailPoetAPIKeyValid();
    expect($result)->false();
  }

  function testItReturnsFalseIfPremiumKeyIsNotSpecified() {
    $this->clearPremiumKey();
    $result = $this->services_checker->isPremiumKeyValid();
    expect($result)->false();
  }

  function testItReturnsTrueIfPremiumKeyIsValid() {
    Setting::setValue(
      Bridge::PREMIUM_KEY_STATE_SETTING_NAME,
      array('state' => Bridge::PREMIUM_KEY_VALID)
    );
    $result = $this->services_checker->isPremiumKeyValid();
    expect($result)->true();
  }

  function testItReturnsFalseIfPremiumKeyIsInvalid() {
    Setting::setValue(
      Bridge::PREMIUM_KEY_STATE_SETTING_NAME,
      array('state' => Bridge::PREMIUM_KEY_INVALID)
    );
    $result = $this->services_checker->isPremiumKeyValid();
    expect($result)->false();
  }

  function testItReturnsFalseIfPremiumKeyIsAlreadyUsed() {
    Setting::setValue(
      Bridge::PREMIUM_KEY_STATE_SETTING_NAME,
      array('state' => Bridge::PREMIUM_KEY_ALREADY_USED)
    );
    $result = $this->services_checker->isPremiumKeyValid();
    expect($result)->false();
  }

  function testItReturnsTrueIfPremiumKeyIsExpiring() {
    Setting::setValue(
      Bridge::PREMIUM_KEY_STATE_SETTING_NAME,
      array(
        'state' => Bridge::PREMIUM_KEY_EXPIRING,
        'data' => array('expire_at' => date('c'))
      )
    );
    $result = $this->services_checker->isPremiumKeyValid();
    expect($result)->true();
  }

  function testItReturnsFalseIfPremiumKeyStateIsUnexpected() {
    Setting::setValue(
      Bridge::PREMIUM_KEY_STATE_SETTING_NAME,
      array(
        'state' => 'unexpected'
      )
    );
    $result = $this->services_checker->isPremiumKeyValid();
    expect($result)->false();
  }

  function testItReturnsFalseIfPremiumKeyStateIsEmpty() {
    Setting::setValue(
      Bridge::PREMIUM_KEY_STATE_SETTING_NAME,
      array(
        'state' => ''
      )
    );
    $result = $this->services_checker->isPremiumKeyValid();
    expect($result)->false();
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

  private function disableMailPoetSendingMethod() {
    Setting::setValue(
      Mailer::MAILER_CONFIG_SETTING_NAME,
      array(
        'method' => 'PHPMail',
      )
    );
  }

  private function clearPremiumKey() {
    Setting::setValue(Bridge::PREMIUM_KEY_SETTING_NAME, '');
  }

  private function fillPremiumKey() {
    Setting::setValue(Bridge::PREMIUM_KEY_SETTING_NAME, '123457890abcdef');
  }
}
