<?php
namespace MailPoet\Cron;

use MailPoet\Cron\Workers\Scheduler as SchedulerWorker;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;

require_once(ABSPATH . 'wp-includes/pluggable.php');

if(!defined('ABSPATH')) exit;

class Daemon {
  public $daemon;
  public $data;
  public $refreshed_token;
  const STATUS_STOPPED = 'stopped';
  const STATUS_STOPPING = 'stopping';
  const STATUS_STARTED = 'started';
  const STATUS_STARTING = 'starting';
  const REQUEST_TIMEOUT = 5;
  private $timer;

  function __construct($data) {
    if(empty($data)) $this->abortWithError(__('Invalid or missing Cron data.'));
    ignore_user_abort();
    $this->daemon = CronHelper::getDaemon();
    $this->token = CronHelper::createToken();
    $this->data = $data;
    $this->timer = microtime(true);
  }

  function run() {
    $daemon = $this->daemon;
    set_time_limit(0);
    if(!$daemon) {
      $this->abortWithError(__('Daemon does not exist.'));
    }
    if(!isset($this->data['token']) ||
      $this->data['token'] !== $daemon['token']
    ) {
      $this->abortWithError(__('Invalid or missing token.'));
    }
    $this->abortIfStopped($daemon);
    try {
      $scheduler = new SchedulerWorker($this->timer);
      $scheduler->process();
      $queue = new SendingQueueWorker($this->timer);
      $queue->process();
    } catch(\Exception $e) {
      // continue processing, no need to handle errors
    }
    $elapsed_time = microtime(true) - $this->timer;
    if($elapsed_time < CronHelper::DAEMON_EXECUTION_LIMIT) {
      sleep(CronHelper::DAEMON_EXECUTION_LIMIT - $elapsed_time);
    }
    // after each execution, re-read daemon data in case its status was changed
    // its status has changed
    $daemon = CronHelper::getDaemon();
    if(!$daemon || $daemon['token'] !== $this->data['token']) {
      $this->terminateRequest();
    }
    $daemon['counter']++;
    $this->abortIfStopped($daemon);
    if($daemon['status'] === self::STATUS_STARTING) {
      $daemon['status'] = self::STATUS_STARTED;
    }
    $daemon['token'] = $this->token;
    CronHelper::saveDaemon($daemon);
    $this->callSelf();
  }

  function abortIfStopped($daemon) {
    if($daemon['status'] === self::STATUS_STOPPED) {
      $this->terminateRequest();
    }
    if($daemon['status'] === self::STATUS_STOPPING) {
      $daemon['status'] = self::STATUS_STOPPED;
      CronHelper::saveDaemon($daemon);
      $this->terminateRequest();
    }
  }

  function abortWithError($message) {
    exit('[mailpoet_cron_error:' . base64_encode($message) . ']');
  }

  function callSelf() {
    CronHelper::accessDaemon($this->token, self::REQUEST_TIMEOUT);
    $this->terminateRequest();
  }

  function terminateRequest() {
    exit;
  }
}