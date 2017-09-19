<?php

namespace MailPoet\Cron;

use MailPoet\Models\Setting;
use MailPoet\Router\Endpoints\CronDaemon as CronDaemonEndpoint;
use MailPoet\Router\Router;
use MailPoet\Util\Security;
use MailPoet\WP\Hooks as WPHooks;

if(!defined('ABSPATH')) exit;

class CronHelper {
  const DAEMON_EXECUTION_LIMIT = 20; // seconds
  const DAEMON_EXECUTION_TIMEOUT = 35; // seconds
  const DAEMON_REQUEST_TIMEOUT = 2; // seconds
  const DAEMON_SETTING = 'cron_daemon';

  static function createDaemon($token) {
    $daemon = array(
      'token' => $token
    );
    self::saveDaemon($daemon);
    return $daemon;
  }

  static function restartDaemon($token) {
    return self::createDaemon($token);
  }

  static function getDaemon() {
    return Setting::getValue(self::DAEMON_SETTING);
  }

  static function saveDaemon($daemon) {
    $daemon['updated_at'] = time();
    return Setting::setValue(
      self::DAEMON_SETTING,
      $daemon
    );
  }

  static function deleteDaemon() {
    return Setting::deleteValue(self::DAEMON_SETTING);
  }

  static function createToken() {
    return Security::generateRandomString();
  }

  static function pingDaemon($timeout = null) {
    $url = self::getCronUrl(
      CronDaemonEndpoint::ACTION_PING_RESPONSE
    );
    $result = self::queryCronUrl($url, $timeout);
    return (is_wp_error($result)) ?
      $result->get_error_message() :
      wp_remote_retrieve_body($result);
  }

  static function accessDaemon($token, $timeout = null) {
    $data = array('token' => $token);
    $url = self::getCronUrl(
      CronDaemonEndpoint::ACTION_RUN,
      $data
    );
    $result = self::queryCronUrl($url, $timeout);
    return wp_remote_retrieve_body($result);
  }

  static function queryCronUrl($url, $timeout) {
    $args = array(
      'blocking' => true,
      'sslverify' => false,
      'timeout' => WPHooks::applyFilters('mailpoet_cron_request_timeout', $timeout),
      'user-agent' => 'MailPoet Cron'
    );
    return wp_remote_get($url, $args);
  }

  static function getCronTimeout($timeout = null) {
    if($timeout) return $timeout;
    $custom_timeout = WPHooks::applyFilters('mailpoet_cron_request_timeout', $timeout);
    if(!$custom_timeout) return self::DAEMON_REQUEST_TIMEOUT;
    return $custom_timeout;
  }

  static function getCronUrl($action, $data = false) {
    $url = Router::buildRequest(
      CronDaemonEndpoint::ENDPOINT,
      $action,
      $data
    );
    $custom_cron_url = WPHooks::applyFilters('mailpoet_cron_request_url', $url);
    return ($custom_cron_url === $url) ?
      str_replace(home_url(), self::getSiteUrl(), $url) :
      $custom_cron_url;
  }

  static function getSiteUrl($site_url = false) {
    // additional check for some sites running inside a virtual machine or behind
    // proxy where there could be different ports (e.g., host:8080 => guest:80)
    $site_url = ($site_url) ? $site_url : home_url();
    $parsed_url = parse_url($site_url);
    $scheme = '';
    if($parsed_url['scheme'] === 'https') {
      $scheme = 'ssl://';
    }
    // 1. if site URL does not contain a port, return the URL
    if(empty($parsed_url['port'])) return $site_url;
    // 2. if site URL contains valid port, try connecting to it
    $fp = @fsockopen($scheme . $parsed_url['host'], $parsed_url['port'], $errno, $errstr, 1);
    if($fp) return $site_url;
    // 3. if connection fails, attempt to connect the standard port derived from URL
    // schema
    $port = (strtolower($parsed_url['scheme']) === 'http') ? 80 : 443;
    $fp = @fsockopen($scheme . $parsed_url['host'], $port, $errno, $errstr, 1);
    if($fp) return sprintf('%s://%s', $parsed_url['scheme'], $parsed_url['host']);
    // 4. throw an error if all connection attempts failed
    throw new \Exception(__('Site URL is unreachable.', 'mailpoet'));
  }

  static function enforceExecutionLimit($timer) {
    $elapsed_time = microtime(true) - $timer;
    if($elapsed_time >= self::DAEMON_EXECUTION_LIMIT) {
      throw new \Exception(__('Maximum execution time has been reached.', 'mailpoet'));
    }
  }
}