<?php
namespace MailPoet\Cron;

use MailPoet\Cron\Workers\Scheduler;
use MailPoet\Cron\Workers\SendingQueue;
use MailPoet\Models\Newsletter;

require_once(ABSPATH . 'wp-includes/pluggable.php');

if(!defined('ABSPATH')) exit;

class Daemon {
  public $daemon;
  public $request_payload;
  public $refreshed_token;
  const daemon_request_timeout = 5;
  private $timer;

  function __construct($request_payload = array()) {
    set_time_limit(0);
    ignore_user_abort();
    $this->daemon = CronHelper::getDaemon();
    $this->token = CronHelper::createToken();
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
      do_action('mailpoet_cron_worker', $this->timer);
    } catch(\Exception $e) {
    }
    $elapsed_time = microtime(true) - $this->timer;
    if($elapsed_time < CronHelper::daemon_execution_limit) {
      sleep(CronHelper::daemon_execution_limit - $elapsed_time);
    }
    // after each execution, re-read daemon data in case it was deleted or
    // its status has changed
    $daemon = CronHelper::getDaemon();
    if(!$daemon || $daemon['token'] !== $this->request_payload['token']) {
      exit;
    }
    $daemon['counter']++;
    $this->abortIfStopped($daemon);
    if($daemon['status'] === 'starting') {
      $daemon['status'] = 'started';
    }
    $daemon['token'] = $this->token;
    CronHelper::saveDaemon($daemon);
    $this->callSelf();
  }

  function abortIfStopped($daemon) {
    if($daemon['status'] === 'stopped') exit;
    if($daemon['status'] === 'stopping') {
      $daemon['status'] = 'stopped';
      CronHelper::saveDaemon($daemon);
      exit;
    }
  }

  function abortWithError($message) {
    exit('[mailpoet_cron_error:' . base64_encode($message) . ']');
  }

  function callSelf() {
    CronHelper::accessDaemon($this->token, self::daemon_request_timeout);
    exit;
  }
}