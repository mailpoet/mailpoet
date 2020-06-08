<?php

namespace MailPoet\Test\Cron\Workers\KeyCheck;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Config\ServicesChecker;
use MailPoet\Cron\Workers\KeyCheck\SendingServiceKeyCheck;
use MailPoet\Mailer\Mailer;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;
use MailPoetVendor\Carbon\Carbon;
use PHPUnit\Framework\MockObject\MockObject;

class SendingServiceKeyCheckTest extends \MailPoetTest {
  public $worker;
  public $mssKey;

  public function _before() {
    parent::_before();
    $this->mssKey = 'some_key';
    $this->worker = new SendingServiceKeyCheck(
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(ServicesChecker::class)
    );
  }

  public function testItRequiresMailPoetMethodToBeSetUp() {
    expect($this->worker->checkProcessingRequirements())->false();
    $this->setMailPoetSendingMethod();
    expect($this->worker->checkProcessingRequirements())->true();
  }

  public function testItRunsEveryHourWhenKeyPendingApproval() {
    // normally next run is scheduled at a start of next day
    expect($this->worker->getNextRunDate()->format('Y-m-d H:i:s'))
      ->equals(Carbon::now()->startOfDay()->addDay()->format('Y-m-d H:i:s'));

    // when pending key approval, next run is scheduled in an hour
    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_MAILPOET]);
    $settings->set(Bridge::API_KEY_SETTING_NAME, 'key');
    $settings->set(Bridge::API_KEY_STATE_SETTING_NAME, [
      'state' => Bridge::KEY_VALID,
      'data' => ['is_approved' => false],
    ]);

    $nextRunDate = $this->worker->getNextRunDate();
    expect($nextRunDate)->greaterThan(Carbon::now()->addMinutes(55));
    expect($nextRunDate)->lessThan(Carbon::now()->addMinutes(65));
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
