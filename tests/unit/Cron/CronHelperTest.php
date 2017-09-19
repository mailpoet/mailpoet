<?php

namespace MailPoet\Test\Cron;

use AspectMock\Test as Mock;
use MailPoet\Cron\CronHelper;
use MailPoet\Models\Setting;

class CronHelperTest extends \MailPoetTest {
  function _before() {
    Setting::setValue('db_version', MAILPOET_VERSION);
  }

  function testItDefinesConstants() {
    expect(CronHelper::DAEMON_EXECUTION_LIMIT)->equals(20);
    expect(CronHelper::DAEMON_EXECUTION_TIMEOUT)->equals(35);
    expect(CronHelper::DAEMON_REQUEST_TIMEOUT)->equals(5);
    expect(CronHelper::DAEMON_SETTING)->equals('cron_daemon');
  }

  function testItCreatesDaemon() {
    $token = 'create_token';
    $time = time();
    CronHelper::createDaemon($token);
    $daemon = Setting::getValue(CronHelper::DAEMON_SETTING);
    expect($daemon)->equals(
      array(
        'token' => $token,
        'updated_at' => $time
      )
    );
  }

  function testItRestartsDaemon() {
    $token = 'restart_token';
    $time = time();
    CronHelper::restartDaemon($token);
    $daemon = Setting::getValue(CronHelper::DAEMON_SETTING);
    expect($daemon)->equals(
      array(
        'token' => $token,
        'updated_at' => $time
      )
    );
  }

  function testItLoadsDaemon() {
    $daemon = array(
      'token' => 'some_token',
      'updated_at' => '12345678'
    );
    Setting::setValue(
      CronHelper::DAEMON_SETTING,
      $daemon
    );
    expect(CronHelper::getDaemon())->equals($daemon);
  }

  function testItSavesDaemon() {
    // when saving daemon, 'updated_at' value should change
    $daemon = array(
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

  function testItCreatesRandomToken() {
    // random token is a string of 5 characters
    $token1 = CronHelper::createToken();
    $token2 = CronHelper::createToken();
    expect($token1)->notEquals($token2);
    expect(is_string($token1))->true();
    expect(strlen($token1))->equals(5);
  }

  function testItGetsSiteUrl() {
    // 1. do nothing when the url does not contain port
    $site_url = 'http://example.com';
    expect(CronHelper::getSiteUrl($site_url))->equals($site_url);

    if(getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') return;

    // 2. when url contains valid port, try connecting to it
    $site_url = 'http://example.com:80';
    expect(CronHelper::getSiteUrl($site_url))->equals($site_url);

    // 3. when url contains invalid port, try connecting to it. when connection fails,
    // another attempt will be made to connect to the standard port derived from URL schema
    $site_url = 'http://example.com:8080';
    expect(CronHelper::getSiteUrl($site_url))->equals('http://example.com');

    // 4. when connection can't be established, exception should be thrown
    $site_url = 'https://invalid:80';
    try {
      CronHelper::getSiteUrl($site_url);
      self::fail('Site URL is unreachable exception not thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('Site URL is unreachable.');
    }
  }

  function testItEnforcesExecutionLimit() {
    $time = microtime(true);
    expect(CronHelper::enforceExecutionLimit($time))->null();
    try {
      CronHelper::enforceExecutionLimit($time - CronHelper::DAEMON_EXECUTION_LIMIT);
      self::fail('Execution limit exception not thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('Maximum execution time has been reached.');
    }
  }

  function testItAllowsSettingCustomCronUrl() {
    $filter = function($url) {
      expect($url)->contains('&endpoint=cron');
      return 'http://custom_cron_url';
    };
    add_filter('mailpoet_cron_request_url', $filter);
    expect(CronHelper::getCronUrl('sample_action'))->equals('http://custom_cron_url');
    remove_filter('mailpoet_cron_request_url', $filter);
  }

  function testItAllowsSettingCustomCronRequestArguments() {
    $request_args = array(
      'blocking' => 'custom_blocking',
      'sslverify' => 'custom_ssl_verify',
      'timeout' => 'custom_timeout',
      'user-agent' => 'custom_user_agent'
    );
    $filter = function($args) use ($request_args) {
      expect($args)->notEmpty();
      return $request_args;
    };
    add_filter('mailpoet_cron_request_args', $filter);
    Mock::func('MailPoet\Cron', 'wp_remote_get', function($url, $args) {
      return $args;
    });
    expect(CronHelper::queryCronUrl('test'))->equals($request_args);
    remove_filter('mailpoet_cron_request_args', $filter);
  }

  function testItReturnsErrorMessageAsPingResponseWhenCronUrlCannotBeAccessed() {
    Mock::double('MailPoet\Cron\CronHelper', array(
      'getSiteUrl' => false
    ));
    expect(CronHelper::pingDaemon())->equals('A valid URL was not provided.');
  }

  function testItPingsDaemon() {
    if(getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') return;
    expect(CronHelper::pingDaemon())->equals('pong');
  }

  function _after() {
    Mock::clean();
    \ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}