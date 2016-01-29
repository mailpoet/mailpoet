<?php
namespace MailPoet\Cron;

use MailPoet\Models\Setting;
use MailPoet\Util\Security;

if(!defined('ABSPATH')) exit;

class Supervisor {
  public $daemon;
  public $token;
  public $force_run;

  function __construct($force_run = false) {
    $this->daemon = self::getDaemon();
    $this->token = Security::generateRandomString();
    $this->force_run = $force_run;
  }

  function checkDaemon() {
    $daemon = $this->daemon;
    if(!$daemon) {
      $daemon = $this->createDaemon();
      return $this->runDaemon($daemon);
    }
    // if the daemon is stopped, return its status and do nothing
    if(!$this->force_run &&
      isset($daemon['status']) &&
      $daemon['status'] === 'stopped'
    ) {
      return $this->formatDaemonStatusMessage($daemon['status']);

    }
    $elapsed_time = time() - (int) $daemon['updated_at'];
    // if it's been less than 40 seconds since last execution and we're not
    // force-running the daemon, return its status and do nothing
    if($elapsed_time < 40 && !$this->force_run) {
      return $this->formatDaemonStatusMessage($daemon['status']);
    }
    // if it's been less than 40 seconds since last execution, we are
    // force-running the daemon and it's either being started or stopped,
    // return its status and do nothing
    elseif($elapsed_time < 40 &&
      $this->force_run &&
      in_array($daemon['status'], array(
        'stopping',
        'starting'
      ))
    ) {
      return $this->formatDaemonStatusMessage($daemon['status']);
    }
    // re-create (restart) daemon
    $this->createDaemon();
    return $this->runDaemon();
  }

  function runDaemon() {
    $payload = serialize(array('token' => $this->token));
    $request = self::accessRemoteUrl(
      '/?mailpoet-api&section=queue&action=run&request_payload=' .
      base64_encode($payload)
    );
    preg_match('/\[(mailpoet_cron_error:.*?)\]/i', $request, $status);
    $daemon = self::getDaemon();
    if(!empty($status) || !$daemon) {
      if(!$daemon) {
        $message = __('Daemon failed to run.');
      } else {
        list(, $message) = explode(':', $status[0]);
        $message = base64_decode($message);
      }
      return $this->formatResult(
        false,
        $message
      );
    }
    return $this->formatDaemonStatusMessage($daemon['status']);
  }

  function createDaemon() {
    $daemon = array(
      'status' => 'starting',
      'counter' => 0,
      'token' => $this->token
    );
    self::saveDaemon($daemon);
    return $daemon;
  }

  static function getDaemon() {
    return Setting::getValue('cron_daemon');
  }

  static function saveDaemon($daemon_data) {
    $daemon_data['updated_at'] = time();
    return Setting::setValue(
      'cron_daemon',
      $daemon_data
    );
  }

  static function accessRemoteUrl($url) {
    $args = array(
      'timeout' => 1,
      'user-agent' => 'MailPoet (www.mailpoet.com) Cron'
    );
    $result = wp_remote_get(
      self::getSiteUrl() . $url,
      $args
    );
    return wp_remote_retrieve_body($result);
  }

  static function getSiteUrl() {
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

  private function formatDaemonStatusMessage($status) {
    return $this->formatResultMessage(
      true,
      sprintf(
        __('Daemon is currently %.'),
        __($status)
      )
    );
  }

  private function formatResultMessage($result, $message) {
    $formattedResult = array(
      'result' => $result
    );
    if(!$result) {
      $formattedResult['errors'] = array($message);
    } else {
      $formattedResult['message'] = $message;
    }
    return $formattedResult;
  }
}