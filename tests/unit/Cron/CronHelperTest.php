<?php

use MailPoet\API\Endpoints\Cron;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Daemon;
use MailPoet\Models\Setting;

class CronHelperTest extends MailPoetTest {
  function testItDefinesConstants() {
    expect(CronHelper::DAEMON_EXECUTION_LIMIT)->equals(20);
    expect(CronHelper::DAEMON_EXECUTION_TIMEOUT)->equals(35);
    expect(CronHelper::DAEMON_REQUEST_TIMEOUT)->equals(2);
    expect(CronHelper::DAEMON_SETTING)->equals('cron_daemon');
  }

  function testItCanCreateDaemon() {
    $token = 'create_token';
    $time = time();
    CronHelper::createDaemon($token);
    $daemon = Setting::getValue(CronHelper::DAEMON_SETTING);
    expect($daemon)->equals(
      array(
        'status' => Daemon::STATUS_STARTING,
        'token' => $token,
        'updated_at' => $time
      )
    );
  }

  function testItCanRestartDaemon() {
    $token = 'restart_token';
    $time = time();
    CronHelper::restartDaemon($token);
    $daemon = Setting::getValue(CronHelper::DAEMON_SETTING);
    expect($daemon)->equals(
      array(
        'status' => Daemon::STATUS_STARTING,
        'token' => $token,
        'updated_at' => $time
      )
    );
  }

  function testItLoadDaemon() {
    $daemon = array(
      'status' => 'created',
      'token' => 'some_token',
      'updated_at' => '12345678'
    );
    Setting::setValue(
      CronHelper::DAEMON_SETTING,
      $daemon
    );
    expect(CronHelper::getDaemon())->equals($daemon);
  }

  function testItCanSaveDaemon() {
    // when saving daemon, 'updated_at' value should change
    $daemon = array(
      'status' => 'created',
      'token' => 'some_token',
      'updated_at' => '12345678'
    );
    Setting::setValue(
      CronHelper::DAEMON_SETTING,
      $daemon
    );
    $time = time();
    CronHelper::saveDaemon($daemon);
    $daemon['updated_at'] = $time;
    expect(CronHelper::getDaemon())->equals($daemon);
  }

  function testItCanCreateRandomToken() {
    // random token is a string of 5 characters
    $token1 =  CronHelper::createToken();
    $token2 =  CronHelper::createToken();
    expect($token1)->notEquals($token2);
    expect(is_string($token1))->true();
    expect(strlen($token1))->equals(5);
  }

  function testItCanGetSiteUrl() {
    // 1. do nothing when the url does not contain port
    $site_url = 'http://example.com';
    expect(CronHelper::getSiteUrl($site_url))->equals($site_url);

    if(getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') return;

    //2. when url contains valid port, try connecting to it
    $site_url = 'http://example.com:80';
    expect(CronHelper::getSiteUrl($site_url))->equals($site_url);
    //3. when url contains invalid port, try connecting to it. when connection fails,
    // another attempt will be made to connect to the standard port derived form URL schema
    $site_url = 'http://example.com:8080';
    expect(CronHelper::getSiteUrl($site_url))->equals('http://example.com');
    //4. when connection can't be established, exception should be thrown
    $site_url = 'https://invalid:80';
    try {
      CronHelper::getSiteUrl($site_url);
      self::fail('Site URL is unreachable exception not thrown.');
    } catch(Exception $e) {
      expect($e->getMessage())->equals('Site URL is unreachable.');
    }
  }

  function testItCanEnforceExecutionLimit() {
    $time = microtime(true);
    expect(CronHelper::enforceExecutionLimit($time))->null();
    try {
      CronHelper::enforceExecutionLimit($time - CronHelper::DAEMON_EXECUTION_LIMIT);
      self::fail('Execution limit exception not thrown.');
    } catch(Exception $e) {
      expect($e->getMessage())->equals('Maximum execution time has been reached.');
    }
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}