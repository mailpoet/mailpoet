<?php
namespace MailPoet\Cron;

use MailPoet\Config\Env;
use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Supervisor {
  public $daemon;

  function __construct($force_start = false) {
    $this->force_start = $force_start;
    if(!Env::isPluginActivated()) {
      throw new \Exception(__('MailPoet is not activated.'));
    }
    $this->daemon = $this->getDaemon();
  }

  function checkDaemon() {
    if(!$this->daemon) {
      return $this->startDaemon();
    }
    if(
      !$this->force_start
      && isset($this->daemon['status'])
      && in_array($this->daemon['status'], array('stopped', 'stopping'))
    ) {
      return $this->daemon['status'];
    }

    $elapsed_time = time() - (int)$this->daemon['updated_at'];

    if($elapsed_time < 40) {
      if(!$this->force_start) {
        return;
      }
      if($this->daemon['status'] === 'stopping' ||
        $this->daemon['status'] === 'starting'
      ) {
        return $this->daemon['status'];
      }
    }
    $this->daemon['status'] = 'starting';
    $this->saveDaemon($this->daemon);
    return $this->startDaemon();
  }

  function startDaemon() {
    if(!session_id()) session_start();
    $sessionId = session_id();
    session_write_close();
    $_SESSION['cron_daemon'] = null;
    $requestPayload = serialize(array('session' => $sessionId));
    self::accessRemoteUrl(
      '/?mailpoet-api&section=queue&action=start&request_payload=' .
      base64_encode($requestPayload)
    );
    session_start();
    if (!isset($_SESSION['cron_daemon'])) {
      throw new \Exception(__('Session cannot be read.'));
    }
    $daemonStatus = $_SESSION['cron_daemon'];
    unset($_SESSION['daemon']);
    session_write_close();
    return $daemonStatus;
  }

  function getDaemon() {
    return Setting::getValue('cron_daemon');
  }

  function saveDaemon($daemon_data) {
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
    wp_remote_get(
      self::getSiteUrl() . $url,
      $args
    );
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
    // throw an error if all connections fail
    throw new \Exception(__('Site URL is unreachable.'));
  }
}