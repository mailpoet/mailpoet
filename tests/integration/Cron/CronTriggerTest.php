<?php

namespace MailPoet\Test\Cron;

use Codeception\Stub;
use MailPoet\Cron\CronTrigger;
use MailPoet\Models\Setting;
use MailPoet\Settings\SettingsController;

require_once('CronTriggerMockMethod.php');
require_once('CronTriggerMockMethodWithException.php');

class CronTriggerTest extends \MailPoetTest {

  /** @var SettingsController */
  private $settings;

  function _before() {
    parent::_before();
    $this->settings = new SettingsController();
    $this->cron_trigger = new CronTrigger($this->settings);
  }

  function testItCanDefineConstants() {
    expect(CronTrigger::DEFAULT_METHOD)->equals('WordPress');
    expect(CronTrigger::SETTING_NAME)->equals('cron_trigger');
    expect(CronTrigger::$available_methods)->equals(
      [
        'mailpoet' => 'MailPoet',
        'wordpress' => 'WordPress',
        'linux_cron' => 'Linux Cron',
        'none' => 'Disabled',
      ]
    );
  }

  function testItCanReturnAvailableMethods() {
    expect($this->cron_trigger->getAvailableMethods())
      ->equals(CronTrigger::$available_methods);
  }

  function testItCanInitializeCronTriggerMethod() {
    $settings_mock = Stub::makeEmpty(
      SettingsController::class,
      ['get' => 'CronTriggerMockMethod']
    );
    $cron_trigger = new CronTrigger($settings_mock);
    expect($cron_trigger->init())->true();
  }

  function testItReturnsFalseWhenItCantInitializeCronTriggerMethod() {
    $settings_mock = Stub::makeEmpty(
      SettingsController::class,
      ['get' => 'MockInvalidMethod']
    );
    $cron_trigger = new CronTrigger($settings_mock);
    expect($cron_trigger->init())->false();
  }

  function testItIgnoresExceptionsThrownFromCronTriggerMethods() {
    $settings_mock = Stub::makeEmpty(
      SettingsController::class,
      ['get' => 'CronTriggerMockMethodWithException']
    );
    $cron_trigger = new CronTrigger($settings_mock);
    expect($cron_trigger->init())->null();
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}
