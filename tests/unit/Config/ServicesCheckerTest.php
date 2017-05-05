<?php

use MailPoet\Config\ServicesChecker;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Setting;
use MailPoet\Services\Bridge;

class ServicesCheckerTest extends MailPoetTest {
  function testItDoesNotCheckMSSKeyIfMPSendingServiceIsDisabled() {
    $this->disableMailPoetSendingMethod();
    $result = ServicesChecker::isMailPoetAPIKeyValid();
    expect($result)->null();
  }

  function testItChecksMSSKeyValidity() {
    $this->setMailPoetSendingMethod();
    Setting::setValue(
      Bridge::API_KEY_STATE_SETTING_NAME,
      array('state' => Bridge::MAILPOET_KEY_VALID)
    );
    $result = ServicesChecker::isMailPoetAPIKeyValid();
    expect($result)->true();

    Setting::setValue(
      Bridge::API_KEY_STATE_SETTING_NAME,
      array('state' => Bridge::MAILPOET_KEY_INVALID)
    );
    $result = ServicesChecker::isMailPoetAPIKeyValid();
    expect($result)->false();

    Setting::setValue(
      Bridge::API_KEY_STATE_SETTING_NAME,
      array(
        'state' => Bridge::MAILPOET_KEY_EXPIRING,
        'data' => array('expire_at' => date('c'))
      )
    );
    $result = ServicesChecker::isMailPoetAPIKeyValid();
    expect($result)->true();

    // unexpected state should be treated as valid
    Setting::setValue(
      Bridge::API_KEY_STATE_SETTING_NAME,
      array(
        'state' => 'unexpected'
      )
    );
    $result = ServicesChecker::isMailPoetAPIKeyValid();
    expect($result)->true();
  }

  function testItDoesNotCheckPremiumKeyIfPremiumKeyIsNotSpecified() {
    $this->clearPremiumKey();
    $result = ServicesChecker::isPremiumKeyValid();
    expect($result)->null();
  }

  function testItChecksPremiumKeyValidity() {
    $this->fillPremiumKey();
    Setting::setValue(
      Bridge::PREMIUM_KEY_STATE_SETTING_NAME,
      array('state' => Bridge::PREMIUM_KEY_VALID)
    );
    $result = ServicesChecker::isPremiumKeyValid();
    expect($result)->true();

    Setting::setValue(
      Bridge::PREMIUM_KEY_STATE_SETTING_NAME,
      array('state' => Bridge::PREMIUM_KEY_INVALID)
    );
    $result = ServicesChecker::isPremiumKeyValid();
    expect($result)->false();

    Setting::setValue(
      Bridge::PREMIUM_KEY_STATE_SETTING_NAME,
      array('state' => Bridge::PREMIUM_KEY_ALREADY_USED)
    );
    $result = ServicesChecker::isPremiumKeyValid();
    expect($result)->false();

    Setting::setValue(
      Bridge::PREMIUM_KEY_STATE_SETTING_NAME,
      array(
        'state' => Bridge::PREMIUM_KEY_EXPIRING,
        'data' => array('expire_at' => date('c'))
      )
    );
    $result = ServicesChecker::isPremiumKeyValid();
    expect($result)->true();

    // unexpected state should be treated as invalid
    Setting::setValue(
      Bridge::PREMIUM_KEY_STATE_SETTING_NAME,
      array(
        'state' => 'unexpected'
      )
    );
    $result = ServicesChecker::isPremiumKeyValid();
    expect($result)->false();

    // empty state should be treated as invalid
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
