<?php
namespace MailPoet\Cron;

use MailPoet\Cron\Workers\SendingQueue;
use MailPoet\Models\Setting;
use MailPoet\Util\Security;

require_once(ABSPATH . 'wp-includes/pluggable.php');

if(!defined('ABSPATH')) exit;

class Daemon {
  public $daemon;
  public $request_payload;
  public $refreshed_token;
  public $timer;

  function __construct($request_payload = array()) {
    set_time_limit(0);
    ignore_user_abort();
    $this->daemon = $this->getDaemon();
    $this->refreshed_token = $this->refreshToken();
    $this->request_payload = $request_payload;
    $this->timer = microtime(true);
  }

  function start() {
    if(!isset($this->request_payload['session'])) {
      $this->abortWithError(__('Missing session ID.'));
    }
    $this->manageSession('start');
    $daemon = $this->daemon;
    if(!$daemon) {
      $this->saveDaemon(
        array(
          'status' => 'starting',
          'counter' => 0
        )
      );
    }
    if($daemon['status'] === 'started') {
      $_SESSION['cron_daemon'] = array(
        'result' => false,
        'errors' => array(__('Daemon already running.'))
      );
    }
    if($daemon['status'] === 'starting') {
      $_SESSION['cron_daemon'] = 'started';
      $_SESSION['cron_daemon'] = array('result' => true);
      $this->manageSession('end');
      $daemon['status'] = 'started';
      $daemon['token'] = $this->refreshed_token;
      $this->saveDaemon($daemon);
      $this->callSelf();
    }
    $this->manageSession('end');
  }

  function run() {
    $allowed_statuses = array(
      'stopping',
      'starting',
      'started'
    );
    if(!$this->daemon || !in_array($this->daemon['status'], $allowed_statuses)) {
      $this->abortWithError(__('Invalid daemon status.'));
    }
    if(!isset($this->request_payload['token']) ||
      $this->request_payload['token'] !== $this->daemon['token']
    ) {
      $this->abortWithError('Invalid token.');
    }
    try {
      $sending_queue = new SendingQueue($this->timer);
      $sending_queue->process();
    } catch(Exception $e) {
    }
    $elapsed_time = microtime(true) - $this->timer;
    if($elapsed_time < 30) {
      sleep(30 - $elapsed_time);
    }
    // after each execution, read daemon in case it's status was modified
    $daemon = $this->getDaemon();
    if($daemon['status'] === 'stopping') $daemon['status'] = 'stopped';
    if($daemon['status'] === 'starting') $daemon['status'] = 'started';
    $daemon['token'] = $this->refreshed_token;
    $daemon['counter']++;
    $this->saveDaemon($daemon);
    if($daemon['status'] === 'started') $this->callSelf();
  }

  function getDaemon() {
    return Setting::getValue('cron_daemon', null);
  }

  function saveDaemon($daemon_data) {
    return Setting::setValue(
      'cron_daemon',
      $daemon_data
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
      session_id($this->request_payload['session']);
      session_start();
    break;
    case 'end':
      session_write_close();
    break;
    }
  }

  function callSelf() {
    $payload = json_encode(array('token' => $this->refreshed_token));
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