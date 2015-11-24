<?php
namespace MailPoet\Queue;

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
    list ($this->queue, $this->queueData) = $this->getQueue();
  }

  function checkQueue() {
    if(!$this->queue) {
      return $this->startQueue();
    } else {
      if(!$this->forceStart && ($this->queueData['status'] === 'paused' ||
          $this->queueData['status'] === 'stopped'
        )
      ) {
        return;
      }
      $currentTime = Carbon::now('UTC');
      $lastUpdateTime = Carbon::createFromFormat(
        'Y-m-d H:i:s',
        $this->queue->updated_at, 'UTC'
      );
      $timeSinceLastStart = $currentTime->diffInSeconds($lastUpdateTime);
      if($timeSinceLastStart < 5) return;
      $this->queueData['status'] = 'paused';
      $this->queue->value = json_encode($this->queueData);
      $this->queue->save();
      return $this->startQueue();
    }
  }

  function startQueue() {
    if(!session_id()) session_start();
    $sessionId = session_id();
    session_write_close();
    $_SESSION['queue'] = null;
    $payload = json_encode(array('session' => $sessionId));
    self::getRemoteUrl(
      '/?mailpoet-api&section=queue&action=start&payload=' . urlencode($payload)
    );
    session_start();
    $queueStatus = $_SESSION['queue'];
    unset($_SESSION['queue']);
    session_write_close();
    return $queueStatus;
  }

  function getQueue() {
    $queue = Setting::where('name', 'queue')
      ->findOne();
    $queueData = ($queue) ? json_decode($queue->value, true) : false;
    return array(
      $queue,
      $queueData
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