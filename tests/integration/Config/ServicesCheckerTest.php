<?php

namespace MailPoet\Test\Config;

use MailPoet\Config\ServicesChecker;
use MailPoet\Mailer\Mailer;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;

class ServicesCheckerTest extends \MailPoetTest {

  /** @var ServicesChecker */
  private $services_checker;

  /** @var SettingsController */
  private $settings;

  function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
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
    $this->settings->set(Bridge::API_KEY_SETTING_NAME, '');
    $result = $this->services_checker->isMailPoetAPIKeyValid();
    expect($result)->false();
  }

  function testItReturnsTrueIfMSSKeyIsValid() {
    $this->settings->set(
      Bridge::API_KEY_STATE_SETTING_NAME,
      ['state' => Bridge::KEY_VALID]
    );
    $result = $this->services_checker->isMailPoetAPIKeyValid();
    expect($result)->true();
  }

  function testItReturnsFalseIfMSSKeyIsInvalid() {
    $this->settings->set(
      Bridge::API_KEY_STATE_SETTING_NAME,
      ['state' => Bridge::KEY_INVALID]
    );
    $result = $this->services_checker->isMailPoetAPIKeyValid();
    expect($result)->false();
  }

  function testItReturnsTrueIfMSSKeyIsExpiring() {
    $this->settings->set(
      Bridge::API_KEY_STATE_SETTING_NAME,
      [
        'state' => Bridge::KEY_EXPIRING,
        'data' => ['expire_at' => date('c')],
      ]
    );
    $result = $this->services_checker->isMailPoetAPIKeyValid();
    expect($result)->true();
  }

  function testItReturnsFalseIfMSSKeyStateIsUnexpected() {
    $this->settings->set(
      Bridge::API_KEY_STATE_SETTING_NAME,
      [
        'state' => 'unexpected',
      ]
    );
    $result = $this->services_checker->isMailPoetAPIKeyValid();
    expect($result)->false();
  }

  function testItReturnsFalseIfMSSKeyStateIsEmpty() {
    $this->settings->set(
      Bridge::API_KEY_STATE_SETTING_NAME,
      [
        'state' => '',
      ]
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
    $this->settings->set(
      Bridge::PREMIUM_KEY_STATE_SETTING_NAME,
      ['state' => Bridge::KEY_VALID]
    );
    $result = $this->services_checker->isPremiumKeyValid();
    expect($result)->true();
  }

  function testItReturnsFalseIfPremiumKeyIsInvalid() {
    $this->settings->set(
      Bridge::PREMIUM_KEY_STATE_SETTING_NAME,
      ['state' => Bridge::KEY_INVALID]
    );
    $result = $this->services_checker->isPremiumKeyValid();
    expect($result)->false();
  }

  function testItReturnsFalseIfPremiumKeyIsAlreadyUsed() {
    $this->settings->set(
      Bridge::PREMIUM_KEY_STATE_SETTING_NAME,
      ['state' => Bridge::KEY_ALREADY_USED]
    );
    $result = $this->services_checker->isPremiumKeyValid();
    expect($result)->false();
  }

  function testItReturnsTrueIfPremiumKeyIsExpiring() {
    $this->settings->set(
      Bridge::PREMIUM_KEY_STATE_SETTING_NAME,
      [
        'state' => Bridge::KEY_EXPIRING,
        'data' => ['expire_at' => date('c')],
      ]
    );
    $result = $this->services_checker->isPremiumKeyValid();
    expect($result)->true();
  }

  function testItReturnsFalseIfPremiumKeyStateIsUnexpected() {
    $this->settings->set(
      Bridge::PREMIUM_KEY_STATE_SETTING_NAME,
      [
        'state' => 'unexpected',
      ]
    );
    $result = $this->services_checker->isPremiumKeyValid();
    expect($result)->false();
  }

  function testItReturnsFalseIfPremiumKeyStateIsEmpty() {
    $this->settings->set(
      Bridge::PREMIUM_KEY_STATE_SETTING_NAME,
      [
        'state' => '',
      ]
    );
    $result = $this->services_checker->isPremiumKeyValid();
    expect($result)->false();
  }

  private function setMailPoetSendingMethod() {
    $this->settings->set(
      Mailer::MAILER_CONFIG_SETTING_NAME,
      [
        'method' => 'MailPoet',
        'mailpoet_api_key' => 'some_key',
      ]
    );
  }

  private function disableMailPoetSendingMethod() {
    $this->settings->set(
      Mailer::MAILER_CONFIG_SETTING_NAME,
      [
        'method' => 'PHPMail',
      ]
    );
  }

  private function clearPremiumKey() {
    $this->settings->set(Bridge::PREMIUM_KEY_SETTING_NAME, '');
  }

  private function fillPremiumKey() {
    $this->settings->set(Bridge::PREMIUM_KEY_SETTING_NAME, '123457890abcdef');
  }
}
