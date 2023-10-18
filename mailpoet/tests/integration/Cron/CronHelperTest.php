<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron;

use Codeception\Stub;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\DaemonHttpRunner;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class CronHelperTest extends \MailPoetTest {

  /** @var SettingsController */
  private $settings;

  /** @var CronHelper */
  private $cronHelper;

  public function _before() {
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
    $this->cronHelper = new CronHelper($this->settings, new WPFunctions);
  }

  public function testItDefinesConstants() {
    verify(CronHelper::DAEMON_EXECUTION_LIMIT)->equals(20);
    verify(CronHelper::DAEMON_REQUEST_TIMEOUT)->equals(5);
    verify(CronHelper::DAEMON_SETTING)->equals('cron_daemon');
  }

  public function testItCreatesDaemon() {
    $token = 'create_token';
    $time = time();
    $this->cronHelper->createDaemon($token);
    $daemon = $this->settings->get(CronHelper::DAEMON_SETTING);
    verify($daemon)->equals(
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

  public function testItRestartsDaemon() {
    $token = 'restart_token';
    $time = time();
    $this->cronHelper->restartDaemon($token);
    $daemon = $this->settings->get(CronHelper::DAEMON_SETTING);
    verify($daemon)->equals(
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

  public function testItLoadsDaemon() {
    $daemon = $this->getDeamonTestData();
    $this->settings->set(
      CronHelper::DAEMON_SETTING,
      $daemon
    );
    verify($this->cronHelper->getDaemon())->equals($daemon);
  }

  public function testItSavesDaemon() {
    // when saving daemon, 'updated_at' value should change
    $daemon = $this->getDeamonTestData();
    $this->settings->set(
      CronHelper::DAEMON_SETTING,
      $daemon
    );
    $time = time();
    $this->cronHelper->saveDaemon($daemon);
    $daemon['updated_at'] = $time;
    verify($this->cronHelper->getDaemon())->equals($daemon);
  }

  public function testItUpdatesDaemonAccessedAt() {
    $daemon = $this->getDeamonTestData();
    $this->settings->set(
      CronHelper::DAEMON_SETTING,
      $daemon
    );
    $time = time();
    $wp = Stub::make(new WPFunctions, [
      'wpRemotePost' => [],
    ]);
    $cronHelper = new CronHelper($this->settings, $wp);
    $cronHelper->accessDaemon('some_token');
    $updatedDaemon = $cronHelper->getDaemon();
    verify($updatedDaemon['run_accessed_at'])->greaterThanOrEqual($time);
    verify($updatedDaemon['run_accessed_at'])->lessThan($time + 2);
  }

  public function testItThrowsAnExceptionIfAccessingNonExistingDaemon() {
    try {
      $this->cronHelper->accessDaemon('some_token');
      $this->fail('An exception should have been thrown.');
    } catch (\LogicException $e) {
      verify($e->getMessage())->equals('Daemon does not exist.');
    }
  }

  public function testItDetectsNotAccessibleDaemon() {
    $time = time();
    $runStartValues = [null, $time - 20];
    foreach ($runStartValues as $runStart) {
      $daemon = $this->getDeamonTestData();
      $daemon['run_accessed_at'] = $time - 10;
      $daemon['run_started_at'] = $runStart;
      $this->settings->set(
        CronHelper::DAEMON_SETTING,
        $daemon
      );
      verify($this->cronHelper->isDaemonAccessible())->false();
    }
  }

  public function testItDetectsAccessibleDaemon() {
    $time = time();
    $daemon = $this->getDeamonTestData();
    $daemon['run_accessed_at'] = $time - 5;
    $daemon['run_started_at'] = $time - 4;
    $this->settings->set(
      CronHelper::DAEMON_SETTING,
      $daemon
    );
    verify($this->cronHelper->isDaemonAccessible())->true();
  }

  public function testItDetectsUnknownStateOfTheDaemon() {
    $time = time();
    $testInputs = [
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
    ];
    foreach ($testInputs as $testInput) {
      $daemon = $this->getDeamonTestData();
      $daemon['run_accessed_at'] = $testInput['run_access'];
      $daemon['run_started_at'] = $testInput['run_start'];
      $this->settings->set(
        CronHelper::DAEMON_SETTING,
        $daemon
      );
      verify($this->cronHelper->isDaemonAccessible())->null();
    }
  }

  public function testItDeactivatesDaemon() {
    $daemon = $this->getDeamonTestData();
    $this->settings->set(
      CronHelper::DAEMON_SETTING,
      $daemon
    );

    $this->cronHelper->deactivateDaemon($daemon);
    $daemon = $this->cronHelper->getDaemon();
    verify($daemon['status'])->equals(CronHelper::DAEMON_STATUS_INACTIVE);
  }

  public function testItSavesLastError() {
    $daemon = $this->getDeamonTestData();
    $this->settings->set(
      CronHelper::DAEMON_SETTING,
      $daemon
    );

    $time = time();
    $this->cronHelper->saveDaemonLastError('error');
    $daemon = $this->cronHelper->getDaemon();
    verify($daemon['last_error'])->equals('error');
    verify($daemon['last_error_date'])->greaterThanOrEqual($time);
  }

  public function testItSavesRunCompletedAt() {
    $daemon = $this->getDeamonTestData();
    $this->settings->set(
      CronHelper::DAEMON_SETTING,
      $daemon
    );

    $this->cronHelper->saveDaemonRunCompleted(123);
    $daemon = $this->cronHelper->getDaemon();
    verify($daemon['run_completed_at'])->equals(123);
  }

  public function testItCreatesRandomToken() {
    // random token is a string of 5 characters
    $token1 = $this->cronHelper->createToken();
    $token2 = $this->cronHelper->createToken();
    verify($token1)->notEquals($token2);
    verify(is_string($token1))->true();
    verify(strlen($token1))->equals(5);
  }

  public function testItGetsSiteUrl() {
    // 1. do nothing when the url does not contain port
    $siteUrl = 'http://example.com';
    verify($this->cronHelper->getSiteUrl($siteUrl))->equals($siteUrl);

    if (getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') $this->markTestSkipped();

    // 2. when url contains valid port, try connecting to it
    $siteUrl = 'http://example.com:80';
    verify($this->cronHelper->getSiteUrl($siteUrl))->equals($siteUrl);

    // 3. when url contains invalid port, try connecting to it. when connection fails,
    // another attempt will be made to connect to the standard port derived from URL schema
    $siteUrl = 'http://example.com:8080';
    verify($this->cronHelper->getSiteUrl($siteUrl))->equals('http://example.com');

    // 4. when connection can't be established, exception should be thrown
    $siteUrl = 'https://invalid:80';
    try {
      $this->cronHelper->getSiteUrl($siteUrl);
      self::fail('Site URL is unreachable exception not thrown.');
    } catch (\Exception $e) {
      verify($e->getMessage())->equals('Site URL is unreachable.');
    }
  }

  public function testItGetsSubsiteUrlOnMultisiteEnvironment() {
    if (is_multisite()) {
      verify($this->cronHelper->getSiteUrl())->stringContainsString((string)getenv('WP_TEST_MULTISITE_SLUG'));
    }
  }

  public function testItEnforcesExecutionLimit() {
    $time = microtime(true);
    verify($this->cronHelper->enforceExecutionLimit($time))->null();
    try {
      $this->cronHelper->enforceExecutionLimit($time - $this->cronHelper->getDaemonExecutionLimit());
      self::fail('Execution limit exception not thrown.');
    } catch (\Exception $e) {
      verify($e->getMessage())->stringStartsWith('The maximum execution time');
    }
  }

  public function testItAllowsSettingCustomCronUrl() {
    $filter = function($url) {
      verify($url)->stringContainsString('&endpoint=cron');
      return 'http://custom_cron_url';
    };
    add_filter('mailpoet_cron_request_url', $filter);
    verify($this->cronHelper->getCronUrl('sample_action'))->equals('http://custom_cron_url');
    remove_filter('mailpoet_cron_request_url', $filter);
  }

  public function testItAllowsSettingCustomCronRequestArguments() {
    $requestArgs = [
      'blocking' => 'custom_blocking',
      'sslverify' => 'custom_ssl_verify',
      'timeout' => 'custom_timeout',
      'user-agent' => 'custom_user_agent',
    ];
    $filter = function($args) use ($requestArgs) {
      verify($args)->notEmpty();
      return $requestArgs;
    };
    $wpRemoteGetArgs = [];
    $wp = Stub::make(new WPFunctions, [
      'wpRemotePost' => function() use (&$wpRemoteGetArgs) {
        return $wpRemoteGetArgs = func_get_args();
      },
    ]);
    $wp->addFilter('mailpoet_cron_request_args', $filter);
    $cronHelper = new CronHelper($this->settings, $wp);
    $cronHelper->queryCronUrl('test');
    verify($wpRemoteGetArgs[1])->equals($requestArgs);

    $wp->removeFilter('mailpoet_cron_request_args', $filter);
  }

  public function testItReturnsErrorMessageAsPingResponseWhenCronUrlCannotBeAccessed() {
    $wp = Stub::make(new WPFunctions, [
      'applyFilters' => function ($name, $args) {
        if ($name !== 'mailpoet_cron_request_url') return $args;
        return 'invalid url';
      },
    ]);
    $cronHelper = new CronHelper($this->settings, $wp);
    verify($cronHelper->pingDaemon())->equals('A valid URL was not provided.');
  }

  public function testItPingsDaemon() {
    if (getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') $this->markTestSkipped();

    $pingResponse = null;
    // because sometimes wp_remote_post ends with timeout we want to try three times
    for ($i = 1; $i <= 3; $i++) {
      $pingResponse = $this->cronHelper->pingDaemon();
      if (strpos('cURL error 28', $pingResponse) !== false) {
        break;
      }
    }
    // raw response is returned
    verify($pingResponse)->equals(DaemonHttpRunner::PING_SUCCESS_RESPONSE);
  }

  public function testItValidatesPingResponse() {
    verify($this->cronHelper->validatePingResponse('pong'))->true();
    verify($this->cronHelper->validatePingResponse('something else'))->false();
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
