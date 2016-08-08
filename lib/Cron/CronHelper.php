<?php
namespace MailPoet\Cron;

use MailPoet\Models\Setting;
use MailPoet\Router\Endpoints\Queue as QueueEndpoint;
use MailPoet\Router\Front as FrontRouter;
use MailPoet\Util\Security;

if(!defined('ABSPATH')) exit;

class CronHelper {
  const DAEMON_EXECUTION_LIMIT = 20;
  const DAEMON_EXECUTION_TIMEOUT = 35;
  const DAEMON_REQUEST_TIMEOUT = 2;
  const DAEMON_SETTING = 'cron_daemon';

  static function createDaemon($token) {
    $daemon = array(
      'status' => Daemon::STATUS_STARTING,
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

  static function stopDaemon() {
    $daemon = self::getDaemon();
    $daemon['status'] = Daemon::STATUS_STOPPED;
    return self::saveDaemon($daemon);
  }

  static function deleteDaemon() {
    return Setting::deleteValue(self::DAEMON_SETTING);
  }

  static function createToken() {
    return Security::generateRandomString();
  }

  static function accessDaemon($token, $timeout = self::DAEMON_REQUEST_TIMEOUT) {
    $data = array('token' => $token);
    $url = FrontRouter::buildRequest(
      QueueEndpoint::ENDPOINT,
      QueueEndpoint::ACTION_RUN,
      $data
    );
    $args = array(
      'blocking' => false,
      'sslverify' => false,
      'timeout' => $timeout,
      'user-agent' => 'MailPoet (www.mailpoet.com) Cron'
    );
    $result = wp_remote_get($url, $args);
    return wp_remote_retrieve_body($result);
  }

  private static function getSiteUrl() {
    // additional check for some sites running on a virtual machine or behind
    // proxy where there could be different ports (e.g., host:8080 => guest:80)

    // if the site URL does not contain a port, return the URL
    if(!preg_match('!^https?://.*?:\d+!', site_url())) return site_url();
    preg_match('!://(?P<host>.*?):(?P<port>\d+)!', site_url(), $server);
    // connect to the URL with port
    $fp = @fsockopen($server['host'], $server['port'], $errno, $errstr, 1);
    if($fp) return site_url();
    // connect to the URL without port
    $fp = @fsockopen($server['host'], $server['port'], $errno, $errstr, 1);
    if($fp) return preg_replace('!(?=:\d+):\d+!', '$1', site_url());
    // throw an error if all connection attempts failed
    throw new \Exception(__('Site URL is unreachable.'));
  }

  static function enforceExecutionLimit($timer) {
    $elapsed_time = microtime(true) - $timer;
    if($elapsed_time >= self::DAEMON_EXECUTION_LIMIT) {
      throw new \Exception(__('Maximum execution time has been reached.'));
    }
  }
}