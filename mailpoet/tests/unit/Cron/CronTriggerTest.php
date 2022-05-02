<?php

namespace MailPoet\Test\Cron;

use Codeception\Stub;
use MailPoet\Cron\CronTrigger;
use MailPoet\Cron\Triggers\MailPoet;
use MailPoet\Cron\Triggers\WordPress;
use MailPoet\Settings\SettingsController;

class CronTriggerTest extends \MailPoetUnitTest {
  public function testItDefinesConstants() {
    expect(CronTrigger::METHOD_LINUX_CRON)->same('Linux Cron');
    expect(CronTrigger::METHOD_WORDPRESS)->same('WordPress');
    expect(CronTrigger::METHODS)->equals([
      'wordpress' => 'WordPress',
      'linux_cron' => 'Linux Cron',
      'none' => 'Disabled',
    ]);
    expect(CronTrigger::DEFAULT_METHOD)->equals('WordPress');
    expect(CronTrigger::SETTING_NAME)->equals('cron_trigger');
  }

  public function testItCanInitializeCronTriggerMethod() {
    $settingsMock = Stub::makeEmpty(SettingsController::class, [
      'get' => CronTrigger::METHOD_WORDPRESS,
    ]);
    $cronTrigger = $this->createCronTrigger($settingsMock);
    expect($cronTrigger->init())->true();
  }

  public function testItReturnsFalseWhenItCantInitializeCronTriggerMethod() {
    $settingsMock = Stub::makeEmpty(SettingsController::class, [
      'get' => 'unknown-method',
    ]);
    $cronTrigger = $this->createCronTrigger($settingsMock);
    expect($cronTrigger->init())->false();
  }

  public function testItIgnoresExceptionsThrownFromCronTriggerMethods() {
    $settingsMock = Stub::makeEmpty(SettingsController::class, [
      'get' => CronTrigger::METHOD_WORDPRESS,
    ]);
    $wordPressTrigger = $this->makeEmpty(WordPress::class, [
      'run' => function () {
        throw new \Exception();
      },
    ]);
    $cronTrigger = $this->createCronTrigger($settingsMock, $wordPressTrigger);
    expect($cronTrigger->init())->null();
  }

  private function createCronTrigger(
    SettingsController $settings,
    WordPress $wordpressTrigger = null
  ) {
    $wordpressTrigger = $wordpressTrigger ?: $this->make(WordPress::class, ['run' => true]);
    return new CronTrigger($wordpressTrigger, $settings);
  }
}
