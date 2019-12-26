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

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->setMailPoetSendingMethod();
    $this->fillPremiumKey();
    $this->services_checker = new ServicesChecker();
  }

  public function testItDoesNotCheckMSSKeyIfMPSendingServiceIsDisabled() {
    $this->disableMailPoetSendingMethod();
    $result = $this->services_checker->isMailPoetAPIKeyValid();
    expect($result)->null();
  }

  public function testItForciblyChecksMSSKeyIfMPSendingServiceIsDisabled() {
    $this->disableMailPoetSendingMethod();
    $result = $this->services_checker->isMailPoetAPIKeyValid(false, true);
    expect($result)->false();
  }

  public function testItReturnsFalseIfMSSKeyIsNotSpecified() {
    $this->settings->set(Bridge::API_KEY_SETTING_NAME, '');
    $result = $this->services_checker->isMailPoetAPIKeyValid();
    expect($result)->false();
  }

  public function testItReturnsTrueIfMSSKeyIsValid() {
    $this->settings->set(
      Bridge::API_KEY_STATE_SETTING_NAME,
      ['state' => Bridge::KEY_VALID]
    );
    $result = $this->services_checker->isMailPoetAPIKeyValid();
    expect($result)->true();
  }

  public function testItReturnsFalseIfMSSKeyIsInvalid() {
    $this->settings->set(
      Bridge::API_KEY_STATE_SETTING_NAME,
      ['state' => Bridge::KEY_INVALID]
    );
    $result = $this->services_checker->isMailPoetAPIKeyValid();
    expect($result)->false();
  }

  public function testItReturnsTrueIfMSSKeyIsExpiring() {
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

  public function testItReturnsFalseIfMSSKeyStateIsUnexpected() {
    $this->settings->set(
      Bridge::API_KEY_STATE_SETTING_NAME,
      [
        'state' => 'unexpected',
      ]
    );
    $result = $this->services_checker->isMailPoetAPIKeyValid();
    expect($result)->false();
  }

  public function testItReturnsFalseIfMSSKeyStateIsEmpty() {
    $this->settings->set(
      Bridge::API_KEY_STATE_SETTING_NAME,
      [
        'state' => '',
      ]
    );
    $result = $this->services_checker->isMailPoetAPIKeyValid();
    expect($result)->false();
  }

  public function testItReturnsFalseIfPremiumKeyIsNotSpecified() {
    $this->clearPremiumKey();
    $result = $this->services_checker->isPremiumKeyValid();
    expect($result)->false();
  }

  public function testItReturnsTrueIfPremiumKeyIsValid() {
    $this->settings->set(
      Bridge::PREMIUM_KEY_STATE_SETTING_NAME,
      ['state' => Bridge::KEY_VALID]
    );
    $result = $this->services_checker->isPremiumKeyValid();
    expect($result)->true();
  }

  public function testItReturnsFalseIfPremiumKeyIsInvalid() {
    $this->settings->set(
      Bridge::PREMIUM_KEY_STATE_SETTING_NAME,
      ['state' => Bridge::KEY_INVALID]
    );
    $result = $this->services_checker->isPremiumKeyValid();
    expect($result)->false();
  }

  public function testItReturnsFalseIfPremiumKeyIsAlreadyUsed() {
    $this->settings->set(
      Bridge::PREMIUM_KEY_STATE_SETTING_NAME,
      ['state' => Bridge::KEY_ALREADY_USED]
    );
    $result = $this->services_checker->isPremiumKeyValid();
    expect($result)->false();
  }

  public function testItReturnsTrueIfPremiumKeyIsExpiring() {
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

  public function testItReturnsFalseIfPremiumKeyStateIsUnexpected() {
    $this->settings->set(
      Bridge::PREMIUM_KEY_STATE_SETTING_NAME,
      [
        'state' => 'unexpected',
      ]
    );
    $result = $this->services_checker->isPremiumKeyValid();
    expect($result)->false();
  }

  public function testItReturnsFalseIfPremiumKeyStateIsEmpty() {
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
