<?php

use MailPoet\Config\ServicesChecker;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Setting;
use MailPoet\Services\Bridge;

class ServicesCheckerTest extends MailPoetTest {
  function testItDoesNotCheckKeyIfMPSendingServiceIsDisabled() {
    $this->disableMailPoetSendingMethod();
    $result = ServicesChecker::checkMailPoetAPIKeyValid();
    expect($result)->null();
  }

  function testItChecksKeyValidity() {
    $this->setMailPoetSendingMethod();
    Setting::setValue(
      Bridge::API_KEY_STATE_SETTING_NAME,
      array('code' => Bridge::MAILPOET_KEY_VALID)
    );
    $result = ServicesChecker::checkMailPoetAPIKeyValid();
    expect($result)->true();

    Setting::setValue(
      Bridge::API_KEY_STATE_SETTING_NAME,
      array('code' => Bridge::MAILPOET_KEY_INVALID)
    );
    $result = ServicesChecker::checkMailPoetAPIKeyValid();
    expect($result)->false();

    Setting::setValue(
      Bridge::API_KEY_STATE_SETTING_NAME,
      array(
        'code' => Bridge::MAILPOET_KEY_EXPIRING,
        'data' => array('expire_at' => date('c'))
      )
    );
    $result = ServicesChecker::checkMailPoetAPIKeyValid();
    expect($result)->true();

    Setting::setValue(
      Bridge::API_KEY_STATE_SETTING_NAME,
      array(
        'code' => 503
      )
    );
    $result = ServicesChecker::checkMailPoetAPIKeyValid();
    expect($result)->true();
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
}