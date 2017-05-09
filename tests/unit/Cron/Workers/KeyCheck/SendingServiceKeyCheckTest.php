<?php

use Codeception\Util\Stub;
use MailPoet\Cron\Workers\KeyCheck\SendingServiceKeyCheck;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Setting;
use MailPoet\Services\Bridge;

class SendingServiceKeyCheckTest extends MailPoetTest {
  function _before() {
    $this->worker = new SendingServiceKeyCheck(microtime(true));
  }

  function testItRequiresMailPoetMethodToBeSetUp() {
    expect($this->worker->checkProcessingRequirements())->false();
    $this->setMailPoetSendingMethod();
    expect($this->worker->checkProcessingRequirements())->true();
  }

  function testItChecksMSSKey() {
    $response = array('code' => Bridge::MAILPOET_KEY_VALID);
    $this->worker->bridge = Stub::make(
      new Bridge,
      array(
        'checkMSSKey' => $response,
        'updateSubscriberCount' => Stub::once()
      ),
      $this
    );
    $this->setMailPoetSendingMethod();
    expect($this->worker->checkKey())->equals($response);
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