<?php
namespace MailPoet\Cron;

use MailPoet\Cron\Workers\SendingQueue;
use MailPoet\Models\Setting;
use MailPoet\Util\Security;

require_once(ABSPATH . 'wp-includes/pluggable.php');

if(!defined('ABSPATH')) exit;

class Daemon {
  function __construct($requestPayload = array()) {
    set_time_limit(0);
    ignore_user_abort();
    list ($this->daemon, $this->daemonData) = $this->getDaemon();
    $this->refreshedToken = $this->refreshToken();
    $this->requestPayload = $requestPayload;
    $this->timer = microtime(true);
  }

  function start() {
    if(!isset($this->requestPayload['session'])) {
      $this->abortWithError(__('Missing session ID.'));
    }
    $this->manageSession('start');
    $daemon = $this->daemon;
    $daemonData = $this->daemonData;
    if(!$daemon) {
      $daemon = Setting::create();
      $daemon->name = 'cron_daemon';
      $daemonData = array(
        'status' => 'starting',
        'counter' => 0
      );
      $daemon->value = json_encode($daemonData);
      $daemon->save();
    }
    if($daemonData['status'] === 'started') {
      $_SESSION['cron_daemon'] = array(
        'result' => false,
        'errors' => array(__('Daemon already running.'))
      );
    }
    if($daemonData['status'] === 'starting') {
      $_SESSION['cron_daemon'] = 'started';
      $_SESSION['cron_daemon'] = array('result' => true);
      $daemonData['status'] = 'started';
      $daemonData['token'] = $this->refreshedToken;
      $this->manageSession('end');
      $daemon->value = json_encode($daemonData);
      $daemon->save();
      $this->callSelf();
    }
    $this->manageSession('end');
  }

  function run() {
    $allowedStatuses = array(
      'stopping',
      'starting',
      'started'
    );
    if(!$this->daemon || !in_array($this->daemonData['status'], $allowedStatuses)) {
      $this->abortWithError(__('Invalid daemon status.'));
    }
    if(!isset($this->requestPayload['token']) ||
      $this->requestPayload['token'] !== $this->daemonData['token']
    ) {
      $this->abortWithError('Invalid token.');
    }
    try {
      $sendingQueue = new SendingQueue($this->timer);
      $sendingQueue->process();
    } catch(Exception $e) {
    }
    $elapsedTime = microtime(true) - $this->timer;
    if($elapsedTime < 30) {
      sleep(30 - $elapsedTime);
    }
    // after each execution, read daemon in case it's status was modified
    list($daemon, $daemonData) = $this->getDaemon();
    if($daemonData['status'] === 'stopping') $daemonData['status'] = 'stopped';
    if($daemonData['status'] === 'starting') $daemonData['status'] = 'started';
    $daemonData['token'] = $this->refreshedToken;
    $daemonData['counter']++;
    $daemon->value = json_encode($daemonData);
    $daemon->save();
    if($daemonData['status'] === 'started') $this->callSelf();
  }

  function getDaemon() {
    $daemon = Setting::where('name', 'cron_daemon')
      ->findOne();
    return array(
      ($daemon) ? $daemon : null,
      ($daemon) ? json_decode($daemon->value, true) : null
    );
  }

  function refreshToken() {
    return Security::generateRandomString();
  }

  function manageSession($action) {
    switch($action) {
    case 'start':
      if(session_id()) {
        session_write_close();
      }
      session_id($this->requestPayload['session']);
      session_start();
    break;
    case 'end':
      session_write_close();
    break;
    }
  }

  function callSelf() {
    $payload = json_encode(array('token' => $this->refreshedToken));
    Supervisor::accessRemoteUrl(
      '/?mailpoet-api&section=queue&action=run&request_payload=' . urlencode($payload)
    );
    exit;
  }

  function abortWithError($error) {
    wp_send_json(
      array(
        'result' => false,
        'errors' => array($error)
      ));
    exit;
  }
}