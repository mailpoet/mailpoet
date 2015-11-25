<?php
namespace MailPoet\Queue;

use MailPoet\Models\Setting;
use MailPoet\Util\Security;

require_once(ABSPATH . 'wp-includes/pluggable.php');

if(!defined('ABSPATH')) exit;

class Daemon {
  function __construct($payload = array()) {
    set_time_limit(0);
    ignore_user_abort();
    list ($this->queue, $this->queueData) = $this->getQueue();
    $this->refreshedToken = $this->refreshToken();
    $this->payload = $payload;
    $this->timer = microtime(true);
  }
  
  function start() {
    if(!isset($this->payload['session'])) {
      $this->abortWithError('missing session ID');
    }
    $this->manageSession('start');
    $queue = $this->queue;
    $queueData = $this->queueData;
    if(!$queue) {
      $queue = Setting::create();
      $queue->name = 'queue';
      $queue->value = json_encode(array('status' => 'stopped'));
      $queue->save();
    }
    if($queueData['status'] !== 'started') {
      $_SESSION['queue'] = 'started';
      $queueData = array(
        'status' => 'started',
        'token' => $this->refreshedToken,
        'counter' => ($queueData['status'] === 'paused') ?
          $queueData['counter'] :
          0
      );
      $_SESSION['queue'] = array('result' => true);
      $this->manageSession('end');
      $queue->value = json_encode($queueData);
      $queue->save();
      $this->callSelf();
    } else {
      $_SESSION['queue'] = array(
        'result' => false,
        'error' => 'already started'
      );
    }
    $this->manageSession('end');
  }
  
  function run() {
    if(!$this->queue || $this->queueData['status'] !== 'started') {
      $this->abortWithError('not running');
    }
    if(!isset($this->payload['token']) ||
      $this->payload['token'] !== $this->queueData['token']
    ) {
      $this->abortWithError('invalid token');
    }
    
    $worker = new Worker();
    $worker->process();
    
    // after each execution, read queue in case it's status was modified
    list($queue, $queueData) = $this->getQueue();
    $queueData['counter']++;
    $queueData['token'] = $this->refreshedToken;
    $queue->value = json_encode($queueData);
    $queue->save();
    $this->callSelf();
  }

  function getQueue() {
    $queue = Setting::where('name', 'queue')
      ->findOne();
    return array(
      ($queue) ? $queue : null,
      ($queue) ? json_decode($queue->value, true) : null
    );
  }

  function refreshToken() {
    return Security::generateRandomString(5);
  }

  function manageSession($action) {
    switch ($action) {
      case 'start':
        if(session_id()) {
          session_write_close();
        }
        session_id($this->payload['session']);
        session_start();
        break;
      case 'end':
        session_write_close();
        break;
    }
  }
  
  function callSelf() {
    $payload = json_encode(array('token' => $this->refreshedToken));
    Supervisor::getRemoteUrl(
      '/?mailpoet-api&section=queue&action=run&payload=' . urlencode($payload)

    );
    exit;
  }

  function abortWithError($error) {
    wp_send_json(
      array(
        'result' => false,
        'error' => $error
      ));
    exit;
  }
}