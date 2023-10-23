<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron;

use Codeception\Stub;
use MailPoet\Cron\CronTrigger;
use MailPoet\Cron\DaemonActionSchedulerRunner;
use MailPoet\Cron\Triggers\WordPress;
use MailPoet\Settings\SettingsController;

class CronTriggerTest extends \MailPoetUnitTest {
  public function testItDefinesConstants() {
    expect(CronTrigger::METHOD_LINUX_CRON)->same('Linux Cron');
    expect(CronTrigger::METHOD_WORDPRESS)->same('WordPress');
    expect(CronTrigger::METHOD_ACTION_SCHEDULER)->same('Action Scheduler');
    verify(CronTrigger::METHODS)->equals([
      'wordpress' => 'WordPress',
      'linux_cron' => 'Linux Cron',
      'action_scheduler' => 'Action Scheduler',
      'none' => 'Disabled',
    ]);
    verify(CronTrigger::DEFAULT_METHOD)->equals('Action Scheduler');
    verify(CronTrigger::SETTING_NAME)->equals('cron_trigger');
  }

  public function testItCanInitializeCronTriggerMethod() {
    $settingsMock = Stub::makeEmpty(SettingsController::class, [
      'get' => CronTrigger::METHOD_WORDPRESS,
    ]);
    $cronTrigger = $this->createCronTrigger($settingsMock);
    expect($cronTrigger->init())->true();
  }

  public function testItDoesntTriggerWordPressMethodInCliEnvironment() {
    $settingsMock = Stub::makeEmpty(SettingsController::class, [
      'get' => CronTrigger::METHOD_WORDPRESS,
    ]);
    $cronTrigger = $this->createCronTrigger($settingsMock);
    expect($cronTrigger->init('cli'))->false();
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
    WordPress $wordpressTrigger = null,
    DaemonActionSchedulerRunner $actionSchedulerRunner = null
  ) {
    $wordpressTrigger = $wordpressTrigger ?: $this->make(WordPress::class, ['run' => true]);
    $actionSchedulerRunner = $actionSchedulerRunner ?: $this->make(DaemonActionSchedulerRunner::class, ['init' => true]);
    return new CronTrigger($wordpressTrigger, $settings, $actionSchedulerRunner);
  }
}
