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
    list ($this->daemon, $this->daemonData) = $this->getDaemon();
    $this->refreshedToken = $this->refreshToken();
    $this->payload = $payload;
    $this->timer = microtime(true);
  }
  
  function start() {
    if(!isset($this->payload['session'])) {
      $this->abortWithError('missing session ID');
    }
    $this->manageSession('start');
    $daemon = $this->daemon;
    $daemonData = $this->daemonData;
    if(!$daemon) {
      $daemon = Setting::create();
      $daemon->name = 'daemon';
      $daemon->value = json_encode(array('status' => 'stopped'));
      $daemon->save();
    }
    if($daemonData['status'] !== 'started') {
      $_SESSION['daemon'] = 'started';
      $daemonData = array(
        'status' => 'started',
        'token' => $this->refreshedToken,
        'counter' => ($daemonData['status'] === 'paused') ?
          $daemonData['counter'] :
          0
      );
      $_SESSION['daemon'] = array('result' => true);
      $this->manageSession('end');
      $daemon->value = json_encode($daemonData);
      $daemon->save();
      $this->callSelf();
    } else {
      $_SESSION['daemon'] = array(
        'result' => false,
        'error' => 'already started'
      );
    }
    $this->manageSession('end');
  }
  
  function run() {
    if(!$this->daemon || $this->daemonData['status'] !== 'started') {
      $this->abortWithError('not running');
    }
    if(!isset($this->payload['token']) ||
      $this->payload['token'] !== $this->daemonData['token']
    ) {
      $this->abortWithError('invalid token');
    }
    
    $worker = new Worker();
    $worker->process();
    $elapsedTime = microtime(true) - $this->timer;
    if ($elapsedTime < 30) {
      sleep(30 - $elapsedTime);
    }

    // after each execution, read daemon in case it's status was modified
    list($daemon, $daemonData) = $this->getDaemon();
    $daemonData['counter']++;
    $daemonData['token'] = $this->refreshedToken;
    $daemon->value = json_encode($daemonData);
    $daemon->save();
    $this->callSelf();
  }

  function getDaemon() {
    $daemon = Setting::where('name', 'daemon')
      ->findOne();
    return array(
      ($daemon) ? $daemon : null,
      ($daemon) ? json_decode($daemon->value, true) : null
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