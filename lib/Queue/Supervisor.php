<?php
namespace MailPoet\Queue;

use Carbon\Carbon;
use MailPoet\Config\Env;
use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Supervisor {
  function __construct($forceStart = false) {
    $this->forceStart = $forceStart;
    $this->checkDBReadiness();
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
      site_url() .
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

  function checkDBReadiness() {
    $db = \ORM::forTable('')
      ->rawQuery(
        'SELECT COUNT(*) as settings FROM information_schema.tables ' .
        'WHERE table_schema = "' . Env::$db_name . '" ' .
        'AND table_name = "' . MP_SETTINGS_TABLE . '";'
      )
      ->findOne()
      ->asArray();
    if((int) $db['settings'] === 0) {
      throw new \Exception('Database has not been configured.');
    }
  }
}