<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers\KeyCheck;

use Codeception\Stub;
use MailPoet\Config\ServicesChecker;
use MailPoet\Cron\CronWorkerScheduler;
use MailPoet\Cron\Workers\KeyCheck\SendingServiceKeyCheck;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerLog;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
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
      $this->diContainer->get(ServicesChecker::class),
      $this->diContainer->get(CronWorkerScheduler::class)
    );
  }

  public function testItRequiresMailPoetMethodToBeSetUp() {
    expect($this->worker->checkProcessingRequirements())->false();
    $this->setMailPoetSendingMethod();
    expect($this->worker->checkProcessingRequirements())->true();
  }

  public function testItRunsEveryHourWhenKeyPendingApproval() {
    // normally next run is scheduled at the next day in first six hours
    $nextRun = $this->worker->getNextRunDate();
    $nextDay = Carbon::now()->startOfDay()->addDay()->addHours(6);
    expect($nextRun->format('Y-m-d'))->equals($nextDay->format('Y-m-d'));
    expect($nextRun)->lessThan($nextDay);

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

  public function testItResumesSendingWhenKeyApproved() {
    MailerLog::pauseSending(MailerLog::getMailerLog());
    expect(MailerLog::isSendingPaused())->true();

    $servicesChecker = $this->make(ServicesChecker::class, [
      'isMailPoetAPIKeyPendingApproval' => Stub::consecutive(true, false),
    ]);

    $worker = new SendingServiceKeyCheck(
      $this->diContainer->get(SettingsController::class),
      $servicesChecker,
      $this->diContainer->get(CronWorkerScheduler::class)
    );

    $bridge = $this->make(new Bridge, [
      'checkMSSKey' => ['code' => Bridge::KEY_VALID],
      'storeMSSKeyAndState' => null,
    ]);
    $worker->bridge = $bridge;

    $this->setMailPoetSendingMethod();
    $worker->checkKey();
    expect(MailerLog::isSendingPaused())->false();
  }

  public function testItChecksMSSKey() {
    $response = ['code' => Bridge::KEY_VALID];
    /** @var MockObject $bridge */
    $bridge = Stub::make(
      new Bridge,
      [
        'checkMSSKey' => $response,
        'storeMSSKeyAndState' => null,
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
    parent::_after();
  }
}
