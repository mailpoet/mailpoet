<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers\KeyCheck;

use Codeception\Util\Stub;
use MailPoet\Cron\CronWorkerScheduler;
use MailPoet\Cron\Workers\KeyCheck\PremiumKeyCheck;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use PHPUnit\Framework\MockObject\MockObject;

class PremiumKeyCheckTest extends \MailPoetTest {
  public $worker;
  public $premiumKey;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->premiumKey = '123457890abcdef';
    $cronWorkerScheduler = $this->diContainer->get(CronWorkerScheduler::class);
    $this->worker = new PremiumKeyCheck($this->settings, $cronWorkerScheduler);
  }

  public function testItRequiresPremiumKeyToBeSpecified() {
    expect($this->worker->checkProcessingRequirements())->false();
    $this->fillPremiumKey();
    expect($this->worker->checkProcessingRequirements())->true();
  }

  public function testItChecksPremiumKey() {
    $response = ['code' => Bridge::KEY_VALID];
    /** @var MockObject $bridge */
    $bridge = Stub::make(
      new Bridge,
      [
        'checkPremiumKey' => $response,
        'storePremiumKeyAndState' => null,
      ],
      $this
    );
    $this->worker->bridge = $bridge;
    $this->worker->bridge->expects($this->once())
      ->method('checkPremiumKey')
      ->with($this->equalTo($this->premiumKey));
    $this->worker->bridge->expects($this->once())
      ->method('storePremiumKeyAndState')
      ->with(
        $this->equalTo($this->premiumKey),
        $this->equalTo($response)
      );
    $this->fillPremiumKey();
    expect($this->worker->checkKey())->equals($response);
  }

  private function fillPremiumKey() {
    $this->settings->set(
      Bridge::PREMIUM_KEY_SETTING_NAME,
      $this->premiumKey
    );
  }

  public function _after() {
    parent::_after();
  }
}
