<?php

namespace MailPoet\Test\Cron\Workers\KeyCheck;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Cron\Workers\KeyCheck\SendingServiceKeyCheck;
use MailPoet\Mailer\Mailer;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;

class SendingServiceKeyCheckTest extends \MailPoetTest {
  public function _before() {
    parent::_before();
    $this->mss_key = 'some_key';
    $this->worker = new SendingServiceKeyCheck($this->di_container->get(SettingsController::class), microtime(true));
  }

  public function testItRequiresMailPoetMethodToBeSetUp() {
    expect($this->worker->checkProcessingRequirements())->false();
    $this->setMailPoetSendingMethod();
    expect($this->worker->checkProcessingRequirements())->true();
  }

  public function testItChecksMSSKey() {
    $response = ['code' => Bridge::KEY_VALID];
    $this->worker->bridge = Stub::make(
      new Bridge,
      [
        'checkMSSKey' => $response,
        'storeMSSKeyAndState' => null,
        'updateSubscriberCount' => Expected::once(),
      ],
      $this
    );
    $this->worker->bridge->expects($this->once())
      ->method('checkMSSKey')
      ->with($this->equalTo($this->mss_key));
    $this->worker->bridge->expects($this->once())
      ->method('storeMSSKeyAndState')
      ->with(
        $this->equalTo($this->mss_key),
        $this->equalTo($response)
      );
    $this->setMailPoetSendingMethod();
    expect($this->worker->checkKey())->equals($response);
  }

  private function setMailPoetSendingMethod() {
    $settings = SettingsController::getInstance();
    $settings->set(
      Mailer::MAILER_CONFIG_SETTING_NAME,
      [
        'method' => 'MailPoet',
        'mailpoet_api_key' => $this->mss_key,
      ]
    );
  }

  public function _after() {
    $this->di_container->get(SettingsRepository::class)->truncate();
  }
}
