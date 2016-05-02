<?php
namespace MailPoet\Cron;

use MailPoet\Cron\Workers\Scheduler;
use MailPoet\Cron\Workers\SendingQueue;

require_once(ABSPATH . 'wp-includes/pluggable.php');

if(!defined('ABSPATH')) exit;

class Daemon {
  public $daemon;
  public $data;
  public $refreshed_token;
  const DAEMON_REQUEST_TIMEOUT = 5;
  private $timer;

  function __construct($data) {
    if(empty($data)) $this->abortWithError(__('Invalid or missing cron data.'));
    set_time_limit(0);
    ignore_user_abort();
    $this->daemon = CronHelper::getDaemon();
    $this->token = CronHelper::createToken();
    $this->data = $data;
    $this->timer = microtime(true);
  }

  function run() {
    $daemon = $this->daemon;
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
      $scheduler = new Scheduler();
      $scheduler->process($this->timer);
      $queue = new SendingQueue();
      $queue->process($this->timer);
    } catch(\Exception $e) {
      // continue processing, no need to catch errors
    }
    $elapsed_time = microtime(true) - $this->timer;
    if($elapsed_time < CronHelper::DAEMON_EXECUTION_LIMIT) {
      sleep(CronHelper::DAEMON_EXECUTION_LIMIT - $elapsed_time);
    }
    // after each execution, re-read daemon data in case it was deleted or
    // its status has changed
    $daemon = CronHelper::getDaemon();
    if(!$daemon || $daemon['token'] !== $this->data['token']) {
      exit;
    }
    $daemon['counter']++;
    $this->abortIfStopped($daemon);
    if($daemon['status'] === CronHelper::DAEMON_STATUS_STARTING) {
      $daemon['status'] = CronHelper::DAEMON_STATUS_STARTED;
    }
    $daemon['token'] = $this->token;
    CronHelper::saveDaemon($daemon);
    $this->callSelf();
  }

  function abortIfStopped($daemon) {
    if($daemon['status'] === CronHelper::DAEMON_STATUS_STOPPED) exit;
    if($daemon['status'] === CronHelper::DAEMON_STATUS_STOPPING) {
      $daemon['status'] = CronHelper::DAEMON_STATUS_STOPPING;
      CronHelper::saveDaemon($daemon);
      exit;
    }
  }

  function abortWithError($message) {
    exit('[mailpoet_cron_error:' . base64_encode($message) . ']');
  }

  function callSelf() {
    CronHelper::accessDaemon($this->token, self::DAEMON_REQUEST_TIMEOUT);
    exit;
  }
}