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
    $this->daemon = Supervisor::getDaemon();
    $this->token = Security::generateRandomString();
    $this->request_payload = $request_payload;
    $this->timer = microtime(true);
  }

  function run() {
    $daemon = $this->daemon;
    if(!$daemon) {
      $this->abortWithError(__('Daemon does not exist.'));
    }
    if(!isset($this->request_payload['token']) ||
      $this->request_payload['token'] !== $daemon['token']
    ) {
      $this->abortWithError(__('Invalid or missing token.'));
    }
    $this->abortIfStopped($daemon);
    try {
      $sending_queue = new SendingQueue($this->timer);
      $sending_queue->process();
    } catch(Exception $e) {
    }
    $elapsed_time = microtime(true) - $this->timer;
    if($elapsed_time < 30) {
      sleep(30 - $elapsed_time);
    }
    // after each execution, re-read daemon data in case its status has changed
    $daemon = Supervisor::getDaemon();
    // if the token has changed, abort further processing
    if ($daemon['token'] !== $this->request_payload['token']) {
      exit;
    }
    $daemon['counter']++;
    $this->abortIfStopped($daemon);
    if($daemon['status'] === 'starting') {
      $daemon['status'] = 'started';
    }
    $daemon['token'] = $this->token;
    Supervisor::saveDaemon($daemon);
    $this->callSelf();
  }

  function abortIfStopped($daemon) {
    if($daemon['status'] === 'stopped') exit;
    if($daemon['status'] === 'stopping') {
      $daemon['status'] = 'stopped';
      Supervisor::saveDaemon($daemon);
      exit;
    }
  }

  function callSelf() {
    $payload = serialize(array('token' => $this->token));
    Supervisor::accessRemoteUrl(
      '/?mailpoet-api&section=queue&action=run&request_payload=' .
      base64_encode($payload)
    );
    exit;
  }

  function abortWithError($message) {
    exit('[mailpoet_cron_error:' . base64_encode($message) . ']');
  }
}