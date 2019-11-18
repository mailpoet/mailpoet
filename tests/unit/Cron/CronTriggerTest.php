<?php

namespace MailPoet\Test\Cron;

use Codeception\Stub;
use MailPoet\Cron\CronTrigger;
use MailPoet\Cron\Triggers\MailPoet;
use MailPoet\Cron\Triggers\WordPress;
use MailPoet\Settings\SettingsController;

class CronTriggerTest extends \MailPoetUnitTest {
  function testItDefinesConstants() {
    expect(CronTrigger::METHOD_LINUX_CRON)->same('Linux Cron');
    expect(CronTrigger::METHOD_MAILPOET)->same('MailPoet');
    expect(CronTrigger::METHOD_WORDPRESS)->same('WordPress');
    expect(CronTrigger::METHODS)->equals([
      'mailpoet' => 'MailPoet',
      'wordpress' => 'WordPress',
      'linux_cron' => 'Linux Cron',
      'none' => 'Disabled',
    ]);
    expect(CronTrigger::DEFAULT_METHOD)->equals('WordPress');
    expect(CronTrigger::SETTING_NAME)->equals('cron_trigger');
  }

  function testItCanInitializeCronTriggerMethod() {
    $settings_mock = Stub::makeEmpty(SettingsController::class, [
      'get' => CronTrigger::METHOD_WORDPRESS,
    ]);
    $cron_trigger = $this->createCronTrigger($settings_mock);
    expect($cron_trigger->init())->true();
  }

  function testItReturnsFalseWhenItCantInitializeCronTriggerMethod() {
    $settings_mock = Stub::makeEmpty(SettingsController::class, [
      'get' => 'unknown-method',
    ]);
    $cron_trigger = $this->createCronTrigger($settings_mock);
    expect($cron_trigger->init())->false();
  }

  function testItIgnoresExceptionsThrownFromCronTriggerMethods() {
    $settings_mock = Stub::makeEmpty(SettingsController::class, [
      'get' => CronTrigger::METHOD_MAILPOET,
    ]);
    $mailpoet_trigger = $this->makeEmpty(MailPoet::class, [
      'run' => function () {
        throw new \Exception();
      },
    ]);
    $cron_trigger = $this->createCronTrigger($settings_mock, $mailpoet_trigger);
    expect($cron_trigger->init())->null();
  }

  private function createCronTrigger(
    SettingsController $settings,
    MailPoet $mailpoet_trigger = null,
    WordPress $wordpress_trigger = null
  ) {
    $mailpoet_trigger = $mailpoet_trigger ?: $this->make(MailPoet::class, ['run' => true]);
    $wordpress_trigger = $wordpress_trigger ?: $this->make(WordPress::class, ['run' => true]);
    return new CronTrigger($mailpoet_trigger, $wordpress_trigger, $settings);
  }
}
