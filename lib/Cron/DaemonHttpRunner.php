<?php
namespace MailPoet\Cron;

use MailPoet\Cron\Triggers\WordPress;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use Tracy\Debugger;

if (!defined('ABSPATH')) exit;


class DaemonHttpRunner {
  public $settings_daemon_data;
  public $timer;
  public $token;

  /** @var Daemon */
  private $daemon;

  /** @var SettingsController */
  private $settings;

  const PING_SUCCESS_RESPONSE = 'pong';

  function __construct(Daemon $daemon = null, SettingsController $settings) {
    $this->settings_daemon_data = CronHelper::getDaemon();
    $this->token = CronHelper::createToken();
    $this->timer = microtime(true);
    $this->daemon = $daemon;
    $this->settings = $settings;
  }

  function ping() {
    // if Tracy enabled & called by 'MailPoet Cron' user agent, disable Tracy Bar
    // (happens in CronHelperTest because it's not a real integration test - calls other WP instance)
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
    if (class_exists(Debugger::class) && $user_agent === 'MailPoet Cron') {
      Debugger::$showBar = false;
    }
    $this->addCacheHeaders();
    $this->terminateRequest(self::PING_SUCCESS_RESPONSE);
  }

  function run($request_data) {
    ignore_user_abort(true);
    if (strpos(@ini_get('disable_functions'), 'set_time_limit') === false) {
      set_time_limit(0);
    }
    $this->addCacheHeaders();
    if (!$request_data) {
      $error = WPFunctions::get()->__('Invalid or missing request data.', 'mailpoet');
    } else {
      if (!$this->settings_daemon_data) {
        $error = WPFunctions::get()->__('Daemon does not exist.', 'mailpoet');
      } else {
        if (!isset($request_data['token']) ||
          $request_data['token'] !== $this->settings_daemon_data['token']
        ) {
          $error = 'Invalid or missing token.';
        }
      }
    }
    if (!empty($error)) {
      return $this->abortWithError($error);
    }
    $this->settings_daemon_data['token'] = $this->token;
    $this->daemon->run($this->settings_daemon_data);
    // If we're using the WordPress trigger, check the conditions to stop cron if necessary
    $enable_cron_self_deactivation = WPFunctions::get()->applyFilters('mailpoet_cron_enable_self_deactivation', false);
    if ($enable_cron_self_deactivation
      && $this->isCronTriggerMethodWordPress()
      && !$this->checkWPTriggerExecutionRequirements()
    ) {
      $this->stopCron();
    } else {
      // if workers took less time to execute than the daemon execution limit,
      // pause daemon execution to ensure that daemon runs only once every X seconds
      $elapsed_time = microtime(true) - $this->timer;
      if ($elapsed_time < CronHelper::DAEMON_EXECUTION_LIMIT) {
        $this->pauseExecution(CronHelper::DAEMON_EXECUTION_LIMIT - $elapsed_time);
      }
    }
    // after each execution, re-read daemon data in case it changed
    $settings_daemon_data = CronHelper::getDaemon();
    if ($this->shouldTerminateExecution($settings_daemon_data)) {
      return $this->terminateRequest();
    }
    return $this->callSelf();
  }

  function pauseExecution($pause_time) {
    return sleep($pause_time);
  }

  function callSelf() {
    CronHelper::accessDaemon($this->token);
    $this->terminateRequest();
  }

  function abortWithError($message) {
    WPFunctions::get()->statusHeader(404, $message);
    exit;
  }

  function terminateRequest($message = false) {
    die($message);
  }

  function isCronTriggerMethodWordPress() {
    $available_methods = CronTrigger::getAvailableMethods();
    return $this->settings->get(CronTrigger::SETTING_NAME . '.method') === $available_methods['wordpress'];
  }

  function checkWPTriggerExecutionRequirements() {
    return WordPress::checkExecutionRequirements();
  }

  function stopCron() {
    return WordPress::stop();
  }

  /**
   * @param array|null $settings_daemon_data
   *
   * @return boolean
   */
  private function shouldTerminateExecution(array $settings_daemon_data = null) {
    return !$settings_daemon_data ||
       $settings_daemon_data['token'] !== $this->token ||
       (isset($settings_daemon_data['status']) && $settings_daemon_data['status'] !== CronHelper::DAEMON_STATUS_ACTIVE);
  }

  private function addCacheHeaders() {
    if (headers_sent()) {
      return;
    }
    // Common Cache Control header. Should be respected by cache proxies and CDNs.
    header('Cache-Control: no-cache');
    // Mark as blacklisted for SG Optimizer for sites hosted on SiteGround.
    header('X-Cache-Enabled: False');
    // Set caching header for LiteSpeed server.
    header('X-LiteSpeed-Cache-Control: no-cache');
  }
}
