<?php
namespace MailPoet\Queue;

use Carbon\Carbon;
use MailPoet\Config\Env;
use MailPoet\Models\Setting;
use MailPoet\Util\Security;

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
      )) {
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
    $args = array(
      'timeout' => 1,
      'user-agent' => 'MailPoet (www.mailpoet.com)'
    );
    $token = Security::generateRandomString(5);
    $payload = json_encode(
      array(
        'token' => $token
      )
    );
    wp_remote_get(
      site_url() .
      '/?mailpoet-api&section=queue&action=start&payload=' .
      urlencode($payload),
      $args
    );
    list ($queue, $queueData) = $this->getQueue();
    return ($queueData && $queueData['token'] === $token) ? true : false;
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