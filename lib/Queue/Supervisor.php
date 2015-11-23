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
      if($timeSinceLastStart < 50) return;
      $this->queueData['status'] = 'paused';
      $this->queue->value = serialize($this->queueData);
      $this->queue->save();
      return $this->startQueue();
    }
  }

  function startQueue() {
    if(!session_id()) session_start();
    $sessionId = session_id();
    session_write_close();
    $args = array(
      'timeout' => 1,
      'user-agent' => 'MailPoet (www.mailpoet.com)'
    );
    $payload = json_encode(
      array(
        'session' => $sessionId
      )
    );
    wp_remote_get(
      self::getSiteUrl() .
      '/?mailpoet-api&section=queue&action=start&payload=' .
      urlencode($payload),
      $args
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
    $queueData = ($queue) ? unserialize($queue->value) : false;
    return array(
      $queue,
      $queueData
    );
  }

  static function getSiteUrl() {
    if (!preg_match('!:\d+/!', site_url())) return site_url();
    preg_match('!http://(?P<host>.*?):(?P<port>\d+)!', site_url(), $server);
    $fp = fsockopen($server['host'], $server['port'], $errno, $errstr, 1);
    return ($fp) ?
      site_url() :
      preg_replace('/(?=:\d+):\d+/', '$1', site_url());
  }
}