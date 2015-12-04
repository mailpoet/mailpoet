<?php
namespace MailPoet\Cron;

use Carbon\Carbon;
use MailPoet\Config\Env;
use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Supervisor {
  function __construct($forceStart = false) {
    $this->forceStart = $forceStart;
    if(!Env::isPluginActivated()) {
      throw new \Exception('Database has not been configured.');
    }
    list ($this->daemon, $this->daemonData) = $this->getDaemon();
  }

  function checkDaemon() {
    if(!$this->daemon) {
      return $this->startDaemon();
    }
    if(!$this->forceStart && $this->daemonData['status'] === 'stopped') {
      return;
    }
    $currentTime = Carbon::now('UTC');
    $lastUpdateTime = Carbon::createFromFormat(
      'Y-m-d H:i:s',
      $this->daemon->updated_at, 'UTC'
    );
    $timeSinceLastStart = $currentTime->diffInSeconds($lastUpdateTime);
    if($timeSinceLastStart < 40) return;
    $this->daemonData['status'] = null;
    $this->daemon->value = json_encode($this->daemonData);
    $this->daemon->save();
    return $this->startDaemon();
  }

  function startDaemon() {
    if(!session_id()) session_start();
    $sessionId = session_id();
    session_write_close();
    $_SESSION['cron_daemon'] = null;
    $payload = json_encode(array('session' => $sessionId));
    self::getRemoteUrl(
      '/?mailpoet-api&section=queue&action=start&payload=' . urlencode($payload)
    );
    session_start();
    $daemonStatus = $_SESSION['cron_daemon'];
    unset($_SESSION['daemon']);
    session_write_close();
    return $daemonStatus;
  }

  function getDaemon() {
    $daemon = Setting::where('name', 'cron_daemon')
      ->findOne();
    $daemonData = ($daemon) ? json_decode($daemon->value, true) : false;
    return array(
      $daemon,
      $daemonData
    );
  }

  static function getRemoteUrl($url) {
    $args = array(
      'timeout' => 1,
      'user-agent' => 'MailPoet (www.mailpoet.com)'
    );
    wp_remote_get(
      self::getSiteUrl() . $url,
      $args
    );
  }

  static function getSiteUrl() {
    if(preg_match('!:\d+/!', site_url())) return site_url();
    preg_match('!http://(?P<host>.*?):(?P<port>\d+)!', site_url(), $server);
    $fp = @fsockopen($server['host'], $server['port'], $errno, $errstr, 1);
    return ($fp) ?
      site_url() :
      preg_replace('/(?=:\d+):\d+/', '$1', site_url());
  }
}