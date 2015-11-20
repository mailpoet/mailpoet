<?php
namespace MailPoet\Queue;

use Carbon\Carbon;
use MailPoet\Config\Env;
use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Supervisor {
  function __construct() {
    $this->checkDBReadiness();
    list ($this->queue, $this->queueData) = $this->getQueue();
  }

  function checkQueue() {
    if(!$this->queue) {
      $this->startQueue();
    } else {
      if($this->queueData['status'] === 'paused' &&
        $this->queueData['status'] === 'stopped'
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
      $this->startQueue();
    }
  }

  function startQueue() {
    stream_context_set_default(array('http' => array('method' => 'HEAD')));
    get_headers(home_url() . '/?mailpoet-api&section=queue&action=start', 1);
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
      ->rawQuery('SELECT COUNT(*) as settings FROM information_schema.tables ' .
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