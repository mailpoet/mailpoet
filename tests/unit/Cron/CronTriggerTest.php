<?php
namespace MailPoet\Test\Cron;

use MailPoet\Cron\CronTrigger;
use MailPoet\Models\Setting;

require_once('CronTriggerMockMethod.php');
require_once('CronTriggerMockMethodWithException.php');

class CronTriggerTest extends \MailPoetTest {
  function _before() {
    $this->cron_trigger = new CronTrigger();
  }

  function testItCanDefineConstants() {
    expect(CronTrigger::DEFAULT_METHOD)->equals('WordPress');
    expect(CronTrigger::SETTING_NAME)->equals('cron_trigger');
    expect(CronTrigger::$available_methods)->equals(
      array(
        'mailpoet' => 'MailPoet',
        'wordpress' => 'WordPress'
      )
    );
  }

  function testItCanConstruct() {
    expect($this->cron_trigger->current_method)
      ->equals(CronTrigger::DEFAULT_METHOD);
  }

  function testItCanGetCurrentMethod() {
    Setting::setValue(CronTrigger::SETTING_NAME, array('method' => 'CronTriggerMockMethod'));
    expect($this->cron_trigger->getCurrentMethod())->equals('CronTriggerMockMethod');
  }

  function testItCanReturnAvailableMethods() {
    expect($this->cron_trigger->getAvailableMethods())
      ->equals(CronTrigger::$available_methods);
  }

  function testItCanInitializeCronTriggerMethod() {
    $cron_trigger = $this->cron_trigger;
    $cron_trigger->current_method = 'CronTriggerMockMethod';
    expect($cron_trigger->init())->true();
  }

  function testItReturnsFalseWhenItCantInitializeCronTriggerMethod() {
    $cron_trigger = $this->cron_trigger;
    $cron_trigger->current_method = 'MockInvalidMethod';
    expect($cron_trigger->init())->false();
  }

  function testItIgnoresExceptionsThrownFromCronTriggerMethods() {
    $cron_trigger = $this->cron_trigger;
    $cron_trigger->current_method = 'CronTriggerMockMethodWithException';
    expect($cron_trigger->init())->null();
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}