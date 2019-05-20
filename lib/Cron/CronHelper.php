<?php

namespace MailPoet\Cron;

use MailPoet\Router\Endpoints\CronDaemon as CronDaemonEndpoint;
use MailPoet\Router\Router;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class CronHelper {
  const DAEMON_EXECUTION_LIMIT = 20; // seconds
  const DAEMON_EXECUTION_TIMEOUT = 35; // seconds
  const DAEMON_REQUEST_TIMEOUT = 5; // seconds
  const DAEMON_SETTING = 'cron_daemon';
  const DAEMON_STATUS_ACTIVE = 'active';
  const DAEMON_STATUS_INACTIVE = 'inactive';

  static function createDaemon($token) {
    $daemon = [
      'token' => $token,
      'status' => self::DAEMON_STATUS_ACTIVE,
      'run_accessed_at' => null,
      'run_started_at' => null,
      'run_completed_at' => null,
      'last_error' => null,
      'last_error_date' => null,
    ];
    self::saveDaemon($daemon);
    return $daemon;
  }

  static function restartDaemon($token) {
    return self::createDaemon($token);
  }

  static function getDaemon() {
    $settings = new SettingsController();
    return $settings->fetch(self::DAEMON_SETTING);
  }

  static function saveDaemonLastError($error) {
    $daemon = self::getDaemon();
    if ($daemon) {
      $daemon['last_error'] = $error;
      $daemon['last_error_date'] = time();
      self::saveDaemon($daemon);
    }
  }

  static function saveDaemonRunCompleted($run_completed_at) {
    $daemon = self::getDaemon();
    if ($daemon) {
      $daemon['run_completed_at'] = $run_completed_at;
      self::saveDaemon($daemon);
    }
  }

  static function saveDaemon($daemon) {
    $daemon['updated_at'] = time();
    $settings = new SettingsController();
    $settings->set(
      self::DAEMON_SETTING,
      $daemon
    );
  }

  static function deactivateDaemon($daemon) {
    $daemon['status'] = self::DAEMON_STATUS_INACTIVE;
    $settings = new SettingsController();
    $settings->set(
      self::DAEMON_SETTING,
      $daemon
    );
  }

  static function createToken() {
    return Security::generateRandomString();
  }

  static function pingDaemon($validate_response = false) {
    $url = self::getCronUrl(
      CronDaemonEndpoint::ACTION_PING_RESPONSE
    );
    $result = self::queryCronUrl($url);
    if (is_wp_error($result)) return $result->get_error_message();
    $wp = new WPFunctions();
    $response = $wp->wpRemoteRetrieveBody($result);
    $response = substr(trim($response), -strlen(DaemonHttpRunner::PING_SUCCESS_RESPONSE)) === DaemonHttpRunner::PING_SUCCESS_RESPONSE ?
      DaemonHttpRunner::PING_SUCCESS_RESPONSE :
      $response;
    return (!$validate_response) ?
      $response :
      $response === DaemonHttpRunner::PING_SUCCESS_RESPONSE;
  }

  static function accessDaemon($token) {
    $data = ['token' => $token];
    $url = self::getCronUrl(
      CronDaemonEndpoint::ACTION_RUN,
      $data
    );
    $daemon = self::getDaemon();
    if (!$daemon) {
      throw new \LogicException('Daemon does not exist.');
    }
    $daemon['run_accessed_at'] = time();
    self::saveDaemon($daemon);
    $result = self::queryCronUrl($url);
    $wp = new WPFunctions();
    return $wp->wpRemoteRetrieveBody($result);
  }

  /**
   * @return boolean|null
   */
  static function isDaemonAccessible() {
    $daemon = self::getDaemon();
    if (!$daemon || !isset($daemon['run_accessed_at']) || $daemon['run_accessed_at'] === null) {
      return null;
    }
    if ($daemon['run_accessed_at'] <= (int)$daemon['run_started_at']) {
      return true;
    }
    if (
      $daemon['run_accessed_at'] + self::DAEMON_REQUEST_TIMEOUT < time() &&
      $daemon['run_accessed_at'] > (int)$daemon['run_started_at']
    ) {
        return false;
    }
    return null;
  }

  static function queryCronUrl($url, $wp = null) {
    if (is_null($wp)) {
      $wp = new WPFunctions();
    }
    $args = $wp->applyFilters(
      'mailpoet_cron_request_args',
      [
        'blocking' => true,
        'sslverify' => false,
        'timeout' => self::DAEMON_REQUEST_TIMEOUT,
        'user-agent' => 'MailPoet Cron',
      ]
    );
    return $wp->wpRemotePost($url, $args);
  }

  static function getCronUrl($action, $data = false, $wp = null) {
    if (is_null($wp)) {
      $wp = new WPFunctions();
    }
    $url = Router::buildRequest(
      CronDaemonEndpoint::ENDPOINT,
      $action,
      $data
    );
    $custom_cron_url = $wp->applyFilters('mailpoet_cron_request_url', $url);
    return ($custom_cron_url === $url) ?
      str_replace(home_url(), self::getSiteUrl(), $url) :
      $custom_cron_url;
  }

  static function getSiteUrl($site_url = false) {
    // additional check for some sites running inside a virtual machine or behind
    // proxy where there could be different ports (e.g., host:8080 => guest:80)
    $site_url = ($site_url) ? $site_url : WPFunctions::get()->homeUrl();
    $parsed_url = parse_url($site_url);
    $scheme = '';
    if ($parsed_url['scheme'] === 'https') {
      $scheme = 'ssl://';
    }
    // 1. if site URL does not contain a port, return the URL
    if (empty($parsed_url['port'])) return $site_url;
    // 2. if site URL contains valid port, try connecting to it
    $fp = @fsockopen($scheme . $parsed_url['host'], $parsed_url['port'], $errno, $errstr, 1);
    if ($fp) return $site_url;
    // 3. if connection fails, attempt to connect the standard port derived from URL
    // schema
    $port = (strtolower($parsed_url['scheme']) === 'http') ? 80 : 443;
    $fp = @fsockopen($scheme . $parsed_url['host'], $port, $errno, $errstr, 1);
    if ($fp) return sprintf('%s://%s', $parsed_url['scheme'], $parsed_url['host']);
    // 4. throw an error if all connection attempts failed
    throw new \Exception(__('Site URL is unreachable.', 'mailpoet'));
  }

  static function enforceExecutionLimit($timer) {
    $elapsed_time = microtime(true) - $timer;
    if ($elapsed_time >= self::DAEMON_EXECUTION_LIMIT) {
      throw new \Exception(__('Maximum execution time has been reached.', 'mailpoet'));
    }
  }
}
