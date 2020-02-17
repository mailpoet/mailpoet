<?php

namespace MailPoet\Test\Cron\Workers\KeyCheck;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Cron\Workers\KeyCheck\SendingServiceKeyCheck;
use MailPoet\Mailer\Mailer;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;
use PHPUnit\Framework\MockObject\MockObject;

class SendingServiceKeyCheckTest extends \MailPoetTest {
  public $worker;
  public $mssKey;

  public function _before() {
    parent::_before();
    $this->mssKey = 'some_key';
    $this->worker = new SendingServiceKeyCheck($this->diContainer->get(SettingsController::class));
  }

  public function testItRequiresMailPoetMethodToBeSetUp() {
    expect($this->worker->checkProcessingRequirements())->false();
    $this->setMailPoetSendingMethod();
    expect($this->worker->checkProcessingRequirements())->true();
  }

  public function testItChecksMSSKey() {
    $response = ['code' => Bridge::KEY_VALID];
    /** @var MockObject $bridge */
    $bridge = Stub::make(
      new Bridge,
      [
        'checkMSSKey' => $response,
        'storeMSSKeyAndState' => null,
        'updateSubscriberCount' => Expected::once(),
      ],
      $this
    );
    $this->worker->bridge = $bridge;
    $this->worker->bridge->expects($this->once())
      ->method('checkMSSKey')
      ->with($this->equalTo($this->mssKey));
    $this->worker->bridge->expects($this->once())
      ->method('storeMSSKeyAndState')
      ->with(
        $this->equalTo($this->mssKey),
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
        'mailpoet_api_key' => $this->mssKey,
      ]
    );
  }

  public function _after() {
    $this->diContainer->get(SettingsRepository::class)->truncate();
  }
}
