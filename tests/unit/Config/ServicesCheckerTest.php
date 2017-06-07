<?php

use MailPoet\Config\ServicesChecker;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Setting;
use MailPoet\Services\Bridge;

class ServicesCheckerTest extends MailPoetTest {
  function _before() {
    $this->setMailPoetSendingMethod();
    $this->fillPremiumKey();
  }

  function testItDoesNotCheckMSSKeyIfMPSendingServiceIsDisabled() {
    $this->disableMailPoetSendingMethod();
    $result = ServicesChecker::isMailPoetAPIKeyValid();
    expect($result)->null();
  }

  function testItForciblyChecksMSSKeyIfMPSendingServiceIsDisabled() {
    $this->disableMailPoetSendingMethod();
    $result = ServicesChecker::isMailPoetAPIKeyValid(false, true);
    expect($result)->false();
  }

  function testItReturnsFalseIfMSSKeyIsNotSpecified() {
    Setting::setValue(Bridge::API_KEY_SETTING_NAME, '');
    $result = ServicesChecker::isMailPoetAPIKeyValid();
    expect($result)->false();
  }

  function testItReturnsTrueIfMSSKeyIsValid() {
    Setting::setValue(
      Bridge::API_KEY_STATE_SETTING_NAME,
      array('state' => Bridge::MAILPOET_KEY_VALID)
    );
    $result = ServicesChecker::isMailPoetAPIKeyValid();
    expect($result)->true();
  }

  function testItReturnsFalseIfMSSKeyIsInvalid() {
    Setting::setValue(
      Bridge::API_KEY_STATE_SETTING_NAME,
      array('state' => Bridge::MAILPOET_KEY_INVALID)
    );
    $result = ServicesChecker::isMailPoetAPIKeyValid();
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
    $result = ServicesChecker::isMailPoetAPIKeyValid();
    expect($result)->true();
  }

  function testItReturnsFalseIfMSSKeyStateIsUnexpected() {
    Setting::setValue(
      Bridge::API_KEY_STATE_SETTING_NAME,
      array(
        'state' => 'unexpected'
      )
    );
    $result = ServicesChecker::isMailPoetAPIKeyValid();
    expect($result)->false();
  }

  function testItReturnsFalseIfMSSKeyStateIsEmpty() {
    Setting::setValue(
      Bridge::API_KEY_STATE_SETTING_NAME,
      array(
        'state' => ''
      )
    );
    $result = ServicesChecker::isMailPoetAPIKeyValid();
    expect($result)->false();
  }

  function testItReturnsFalseIfPremiumKeyIsNotSpecified() {
    $this->clearPremiumKey();
    $result = ServicesChecker::isPremiumKeyValid();
    expect($result)->false();
  }

  function testItReturnsTrueIfPremiumKeyIsValid() {
    Setting::setValue(
      Bridge::PREMIUM_KEY_STATE_SETTING_NAME,
      array('state' => Bridge::PREMIUM_KEY_VALID)
    );
    $result = ServicesChecker::isPremiumKeyValid();
    expect($result)->true();
  }

  function testItReturnsFalseIfPremiumKeyIsInvalid() {
    Setting::setValue(
      Bridge::PREMIUM_KEY_STATE_SETTING_NAME,
      array('state' => Bridge::PREMIUM_KEY_INVALID)
    );
    $result = ServicesChecker::isPremiumKeyValid();
    expect($result)->false();
  }

  function testItReturnsFalseIfPremiumKeyIsAlreadyUsed() {
    Setting::setValue(
      Bridge::PREMIUM_KEY_STATE_SETTING_NAME,
      array('state' => Bridge::PREMIUM_KEY_ALREADY_USED)
    );
    $result = ServicesChecker::isPremiumKeyValid();
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
    $result = ServicesChecker::isPremiumKeyValid();
    expect($result)->true();
  }

  function testItReturnsFalseIfPremiumKeyStateIsUnexpected() {
    Setting::setValue(
      Bridge::PREMIUM_KEY_STATE_SETTING_NAME,
      array(
        'state' => 'unexpected'
      )
    );
    $result = ServicesChecker::isPremiumKeyValid();
    expect($result)->false();
  }

  function testItReturnsFalseIfPremiumKeyStateIsEmpty() {
    Setting::setValue(
      Bridge::PREMIUM_KEY_STATE_SETTING_NAME,
      array(
        'state' => ''
      )
    );
    $result = ServicesChecker::isPremiumKeyValid();
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
