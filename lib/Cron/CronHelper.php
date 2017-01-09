<?php
namespace MailPoet\Cron;

use MailPoet\Models\Setting;
use MailPoet\Router\Endpoints\CronDaemon as CronDaemonEndpoint;
use MailPoet\Router\Router;
use MailPoet\Util\Security;

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

  static function pingDaemon() {
    $url = Router::buildRequest(
      CronDaemonEndpoint::ENDPOINT,
      CronDaemonEndpoint::ACTION_PING_RESPONSE
    );
    $url = str_replace(home_url(), self::getSiteUrl(), $url);
    $args = array(
      'blocking' => true,
      'sslverify' => false,
      'timeout' => self::DAEMON_REQUEST_TIMEOUT,
      'user-agent' => 'MailPoet (www.mailpoet.com) Cron'
    );
    $result = wp_remote_get($url, $args);
    return wp_remote_retrieve_body($result) === 'pong';
  }

  static function accessDaemon($token, $timeout = self::DAEMON_REQUEST_TIMEOUT) {
    $data = array('token' => $token);
    $url = Router::buildRequest(
      CronDaemonEndpoint::ENDPOINT,
      CronDaemonEndpoint::ACTION_RUN,
      $data
    );
    $url = str_replace(home_url(), self::getSiteUrl(), $url);
    $args = array(
      'blocking' => true,
      'sslverify' => false,
      'timeout' => $timeout,
      'user-agent' => 'MailPoet (www.mailpoet.com) Cron'
    );
    $result = wp_remote_get($url, $args);
    return wp_remote_retrieve_body($result);
  }

  static function getSiteUrl($site_url = false) {
    // additional check for some sites running inside a virtual machine or behind
    // proxy where there could be different ports (e.g., host:8080 => guest:80)
    $site_url = ($site_url) ? $site_url : home_url();
    $parsed_url = parse_url($site_url);
    // 1. if site URL does not contain a port, return the URL
    if(empty($parsed_url['port'])) return $site_url;
    // 2. if site URL contains valid port, try connecting to it
    $fp = @fsockopen($parsed_url['host'], $parsed_url['port'], $errno, $errstr, 1);
    if($fp) return $site_url;
    // 3. if connection fails, attempt to connect the standard port derived from URL
    // schema
    $port = (strtolower($parsed_url['scheme']) === 'http') ? 80 : 443;
    $fp = @fsockopen($parsed_url['host'], $port, $errno, $errstr, 1);
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