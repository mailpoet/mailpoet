<?php

namespace MailPoet\Test\Cron;

use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Supervisor;
use MailPoet\Models\Setting;
use MailPoet\Settings\SettingsController;

class SupervisorTest extends \MailPoetTest {
  /** @var SettingsController */
  private $settings;

  function _before() {
    parent::_before();
    // cron trigger is by default set to 'WordPress'; when it runs and does not
    // detect any queues to process, it deletes the daemon setting, so Supervisor and
    // CronHelper's getDaemon() methods do not work. for that matter, we need to set
    // the trigger setting to anything but 'WordPress'.
    $this->settings = new SettingsController();
    $this->settings->set('cron_trigger', [
      'method' => 'none',
    ]);
  }

  function testItCanConstruct() {
    if (getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') $this->markTestSkipped();
    $supervisor = new Supervisor();
    expect($supervisor->token)->notEmpty();
    expect($supervisor->daemon)->notEmpty();
  }

  function testItCreatesDaemonWhenOneDoesNotExist() {
    if (getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') $this->markTestSkipped();
    expect($this->settings->get(CronHelper::DAEMON_SETTING))->null();
    $supervisor = new Supervisor();
    expect($supervisor->getDaemon())->notEmpty();
  }

  function testItReturnsDaemonWhenOneExists() {
    if (getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') $this->markTestSkipped();
    $supervisor = new Supervisor();
    expect($supervisor->getDaemon())->equals($supervisor->daemon);
  }

  function testItDoesNothingWhenDaemonExecutionDurationIsBelowLimit() {
    if (getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') $this->markTestSkipped();
    $supervisor = new Supervisor();
    expect($supervisor->checkDaemon())
      ->equals($supervisor->daemon);
  }

  function testRestartsDaemonWhenExecutionDurationIsAboveLimit() {
    if (getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') $this->markTestSkipped();
    $supervisor = new Supervisor();
    $supervisor->daemon['updated_at'] = time() - CronHelper::getDaemonExecutionTimeout();
    $daemon = $supervisor->checkDaemon();
    expect(is_int($daemon['updated_at']))->true();
    expect($daemon['updated_at'])->notEquals($supervisor->daemon['updated_at']);
    expect($daemon['status'])->equals(CronHelper::DAEMON_STATUS_ACTIVE);
  }

  function testRestartsDaemonWhenItIsInactive() {
    if (getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') $this->markTestSkipped();
    $supervisor = new Supervisor();
    $supervisor->daemon['updated_at'] = time();
    $supervisor->daemon['status'] = CronHelper::DAEMON_STATUS_INACTIVE;
    $daemon = $supervisor->checkDaemon();
    expect($daemon['status'])->equals(CronHelper::DAEMON_STATUS_ACTIVE);
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}
