<?php

namespace MailPoet\Test\Cron;

use Codeception\Stub;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\DaemonHttpRunner;
use MailPoet\Models\Setting;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class CronHelperTest extends \MailPoetTest {

  /** @var SettingsController */
  private $settings;

  /** @var CronHelper */
  private $cron_helper;

  function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->settings->set('db_version', MAILPOET_VERSION);
    // Disable cron trigger to not run tasks like migration when pinging the daemon
    $this->settings->set('cron_trigger', [
      'method' => 'none',
    ]);
    $this->settings->set('sender', [
      'name' => 'John Doe',
      'address' => 'john.doe@example.com',
    ]);
    $this->cron_helper = new CronHelper($this->settings, new WPFunctions);
  }

  function testItDefinesConstants() {
    expect(CronHelper::DAEMON_EXECUTION_LIMIT)->equals(20);
    expect(CronHelper::DAEMON_REQUEST_TIMEOUT)->equals(5);
    expect(CronHelper::DAEMON_SETTING)->equals('cron_daemon');
  }

  function testItCreatesDaemon() {
    $token = 'create_token';
    $time = time();
    $this->cron_helper->createDaemon($token);
    $daemon = $this->settings->get(CronHelper::DAEMON_SETTING);
    expect($daemon)->equals(
      [
        'token' => $token,
        'status' => CronHelper::DAEMON_STATUS_ACTIVE,
        'updated_at' => $time,
        'run_accessed_at' => null,
        'run_started_at' => null,
        'run_completed_at' => null,
        'last_error' => null,
        'last_error_date' => null,
      ]
    );
  }

  function testItRestartsDaemon() {
    $token = 'restart_token';
    $time = time();
    $this->cron_helper->restartDaemon($token);
    $daemon = $this->settings->get(CronHelper::DAEMON_SETTING);
    expect($daemon)->equals(
      [
        'token' => $token,
        'status' => CronHelper::DAEMON_STATUS_ACTIVE,
        'updated_at' => $time,
        'run_accessed_at' => null,
        'run_started_at' => null,
        'run_completed_at' => null,
        'last_error' => null,
        'last_error_date' => null,
      ]
    );
  }

  function testItLoadsDaemon() {
    $daemon = $this->getDeamonTestData();
    $this->settings->set(
      CronHelper::DAEMON_SETTING,
      $daemon
    );
    expect($this->cron_helper->getDaemon())->equals($daemon);
  }

  function testItSavesDaemon() {
    // when saving daemon, 'updated_at' value should change
    $daemon = $this->getDeamonTestData();
    $this->settings->set(
      CronHelper::DAEMON_SETTING,
      $daemon
    );
    $time = time();
    $this->cron_helper->saveDaemon($daemon);
    $daemon['updated_at'] = $time;
    expect($this->cron_helper->getDaemon())->equals($daemon);
  }

  function testItUpdatesDaemonAccessedAt() {
    $daemon = $this->getDeamonTestData();
    $this->settings->set(
      CronHelper::DAEMON_SETTING,
      $daemon
    );
    $time = time();
    $wp = Stub::make(new WPFunctions, [
      'wpRemotePost' => [],
    ]);
    $cron_helper = new CronHelper($this->settings, $wp);
    $cron_helper->accessDaemon('some_token');
    $updated_daemon = $cron_helper->getDaemon();
    expect($updated_daemon['run_accessed_at'])->greaterOrEquals($time);
    expect($updated_daemon['run_accessed_at'])->lessThan($time + 2);
  }

  function testItThrowsAnExceptionIfAccessingNonExistingDaemon() {
    try {
      $this->cron_helper->accessDaemon('some_token');
      $this->fail('An exception should have been thrown.');
    } catch (\LogicException $e) {
      expect($e->getMessage())->equals('Daemon does not exist.');
    }
  }

  function testItDetectsNotAccessibleDaemon() {
    $time = time();
    $run_start_values = [null, $time - 20];
    foreach ($run_start_values as $run_start) {
      $daemon = $this->getDeamonTestData();
      $daemon['run_accessed_at'] = $time - 10;
      $daemon['run_started_at'] = $run_start;
      $this->settings->set(
        CronHelper::DAEMON_SETTING,
        $daemon
      );
      expect($this->cron_helper->isDaemonAccessible())->false();
    }
  }

  function testItDetectsAccessibleDaemon() {
    $time = time();
    $daemon = $this->getDeamonTestData();
    $daemon['run_accessed_at'] = $time - 5;
    $daemon['run_started_at'] = $time - 4;
    $this->settings->set(
      CronHelper::DAEMON_SETTING,
      $daemon
    );
    expect($this->cron_helper->isDaemonAccessible())->true();
  }

  function testItDetectsUnknownStateOfTheDaemon() {
    $time = time();
    $test_inputs = [
      [
        'run_access' => null,
        'run_start' => null,
      ],
      [
        'run_access' => $time - 4,
        'run_start' => null,
      ],
      [
        'run_access' => $time - 4,
        'run_start' => $time - 10,
      ],
      null,
    ];
    foreach ($test_inputs as $test_input) {
      $daemon = $this->getDeamonTestData();
      $daemon['run_accessed_at'] = $test_input['run_access'];
      $daemon['run_started_at'] = $test_input['run_start'];
      $this->settings->set(
        CronHelper::DAEMON_SETTING,
        $daemon
      );
      expect($this->cron_helper->isDaemonAccessible())->null();
    }
  }

  function testItDeactivatesDaemon() {
    $daemon = $this->getDeamonTestData();
    $this->settings->set(
      CronHelper::DAEMON_SETTING,
      $daemon
    );

    $this->cron_helper->deactivateDaemon($daemon);
    $daemon = $this->cron_helper->getDaemon();
    expect($daemon['status'])->equals(CronHelper::DAEMON_STATUS_INACTIVE);
  }

  function testItSavesLastError() {
    $daemon = $this->getDeamonTestData();
    $this->settings->set(
      CronHelper::DAEMON_SETTING,
      $daemon
    );

    $time = time();
    $this->cron_helper->saveDaemonLastError('error');
    $daemon = $this->cron_helper->getDaemon();
    expect($daemon['last_error'])->equals('error');
    expect($daemon['last_error_date'])->greaterOrEquals($time);
  }


  function testItSavesRunCompletedAt() {
    $daemon = $this->getDeamonTestData();
    $this->settings->set(
      CronHelper::DAEMON_SETTING,
      $daemon
    );

    $this->cron_helper->saveDaemonRunCompleted(123);
    $daemon = $this->cron_helper->getDaemon();
    expect($daemon['run_completed_at'])->equals(123);
  }

  function testItCreatesRandomToken() {
    // random token is a string of 5 characters
    $token1 = $this->cron_helper->createToken();
    $token2 = $this->cron_helper->createToken();
    expect($token1)->notEquals($token2);
    expect(is_string($token1))->true();
    expect(strlen($token1))->equals(5);
  }

  function testItGetsSiteUrl() {
    // 1. do nothing when the url does not contain port
    $site_url = 'http://example.com';
    expect($this->cron_helper->getSiteUrl($site_url))->equals($site_url);

    if (getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') $this->markTestSkipped();

    // 2. when url contains valid port, try connecting to it
    $site_url = 'http://example.com:80';
    expect($this->cron_helper->getSiteUrl($site_url))->equals($site_url);

    // 3. when url contains invalid port, try connecting to it. when connection fails,
    // another attempt will be made to connect to the standard port derived from URL schema
    $site_url = 'http://example.com:8080';
    expect($this->cron_helper->getSiteUrl($site_url))->equals('http://example.com');

    // 4. when connection can't be established, exception should be thrown
    $site_url = 'https://invalid:80';
    try {
      $this->cron_helper->getSiteUrl($site_url);
      self::fail('Site URL is unreachable exception not thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Site URL is unreachable.');
    }
  }

  function testItGetsSubsiteUrlOnMultisiteEnvironment() {
    if ((boolean)getenv('MULTISITE') === true) {
      expect($this->cron_helper->getSiteUrl())->contains(getenv('WP_TEST_MULTISITE_SLUG'));
    }
  }

  function testItEnforcesExecutionLimit() {
    $time = microtime(true);
    expect($this->cron_helper->enforceExecutionLimit($time))->null();
    try {
      $this->cron_helper->enforceExecutionLimit($time - $this->cron_helper->getDaemonExecutionLimit());
      self::fail('Execution limit exception not thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Maximum execution time has been reached.');
    }
  }

  function testItAllowsSettingCustomCronUrl() {
    $filter = function($url) {
      expect($url)->contains('&endpoint=cron');
      return 'http://custom_cron_url';
    };
    add_filter('mailpoet_cron_request_url', $filter);
    expect($this->cron_helper->getCronUrl('sample_action'))->equals('http://custom_cron_url');
    remove_filter('mailpoet_cron_request_url', $filter);
  }

  function testItAllowsSettingCustomCronRequestArguments() {
    $request_args = [
      'blocking' => 'custom_blocking',
      'sslverify' => 'custom_ssl_verify',
      'timeout' => 'custom_timeout',
      'user-agent' => 'custom_user_agent',
    ];
    $filter = function($args) use ($request_args) {
      expect($args)->notEmpty();
      return $request_args;
    };
    $wp_remote_get_args = [];
    $wp = Stub::make(new WPFunctions, [
      'wpRemotePost' => function() use (&$wp_remote_get_args) {
        return $wp_remote_get_args = func_get_args();
      },
    ]);
    $wp->addFilter('mailpoet_cron_request_args', $filter);
    $cron_helper = new CronHelper($this->settings, $wp);
    $cron_helper->queryCronUrl('test');
    expect($wp_remote_get_args[1])->equals($request_args);

    $wp->removeFilter('mailpoet_cron_request_args', $filter);
  }

  function testItReturnsErrorMessageAsPingResponseWhenCronUrlCannotBeAccessed() {
    $wp = Stub::make(new WPFunctions, [
      'applyFilters' => [],
    ]);
    $cron_helper = new CronHelper($this->settings, $wp);
    expect($cron_helper->pingDaemon())->equals('A valid URL was not provided.');
  }

  function testItPingsDaemon() {
    if (getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') $this->markTestSkipped();
    // raw response is returned
    expect($this->cron_helper->pingDaemon())->equals(DaemonHttpRunner::PING_SUCCESS_RESPONSE);
  }

  function testItValidatesPingResponse() {
    expect($this->cron_helper->validatePingResponse('pong'))->true();
    expect($this->cron_helper->validatePingResponse('something else'))->false();
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }

  private function getDeamonTestData() {
    return [
      'token' => 'some_token',
      'status' => CronHelper::DAEMON_STATUS_ACTIVE,
      'updated_at' => 12345678,
      'run_accessed_at' => null,
      'run_started_at' => null,
      'run_completed_at' => null,
      'last_error' => null,
      'last_error_date' => null,
    ];
  }
}
