<?php

use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Supervisor;
use MailPoet\Models\Setting;

class SupervisorTest extends MailPoetTest {
  function _before() {
    // cron trigger is by default set to 'WordPress'; when it runs and does not
    // detect any queues to process, it deletes the daemon setting, so Supervisor and
    // CronHelper's getDaemon() methods do not work. for that matter, we need to set
    // the trigger setting to anything but 'WordPress'.
    Setting::setValue('cron_trigger', array(
      'method' => 'none'
    ));
  }

  function testItCanConstruct() {
    if(getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') return;
    $supervisor = new Supervisor();
    expect($supervisor->token)->notEmpty();
    expect($supervisor->daemon)->notEmpty();
  }

  function testItCreatesDaemonWhenOneDoesNotExist() {
    if(getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') return;
    expect(Setting::getValue(CronHelper::DAEMON_SETTING))->null();
    $supervisor = new Supervisor();
    expect($supervisor->getDaemon())->notEmpty();
  }

  function testItReturnsDaemonWhenOneExists() {
    if(getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') return;
    $supervisor = new Supervisor();
    expect($supervisor->getDaemon())->equals($supervisor->daemon);
  }

  function testItDoesNothingWhenDaemonExecutionDurationIsBelowLimit() {
    if(getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') return;
    $supervisor = new Supervisor();
    expect($supervisor->checkDaemon())
      ->equals($supervisor->daemon);
  }

  function testRestartsDaemonWhenExecutionDurationIsAboveLimit() {
    if(getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') return;
    $supervisor = new Supervisor();
    $supervisor->daemon['updated_at'] = time() - CronHelper::DAEMON_EXECUTION_TIMEOUT;
    $daemon = $supervisor->checkDaemon();
    expect(is_int($daemon['updated_at']))->true();
    expect($daemon['updated_at'])->notEquals($supervisor->daemon['updated_at']);
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}