<?php
namespace MailPoet\Cron;
use MailPoet\Cron\Workers\Scheduler as SchedulerWorker;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Cron\Workers\Bounce as BounceWorker;
use MailPoet\Cron\Workers\KeyCheck\PremiumKeyCheck as PremiumKeyCheckWorker;
use MailPoet\Cron\Workers\KeyCheck\SendingServiceKeyCheck as SendingServiceKeyCheckWorker;

if(!defined('ABSPATH')) exit;
require_once(ABSPATH . 'wp-includes/pluggable.php');

class Daemon {
  public $daemon;
  public $request_data;
  public $timer;
  const REQUEST_TIMEOUT = 5; // seconds

  function __construct($request_data = false) {
    $this->request_data = $request_data;
    $this->daemon = CronHelper::getDaemon();
    $this->token = CronHelper::createToken();
    $this->timer = microtime(true);
  }

  function ping() {
    $this->terminateRequest('pong');
  }

  function run() {
    ignore_user_abort(true);
    if(!$this->request_data) {
      $error = __('Invalid or missing request data.', 'mailpoet');
    } else {
      if(!$this->daemon) {
        $error = __('Daemon does not exist.', 'mailpoet');
      } else {
        if(!isset($this->request_data['token']) ||
          $this->request_data['token'] !== $this->daemon['token']
        ) {
          $error = 'Invalid or missing token.';
        }
      }
    }
    if(!empty($error)) {
      return $this->abortWithError($error);
    }
    $daemon = $this->daemon;
    $daemon['token'] = $this->token;
    CronHelper::saveDaemon($daemon);
    try {
      $this->executeScheduleWorker();
      $this->executeQueueWorker();
      $this->executeSendingServiceKeyCheckWorker();
      $this->executePremiumKeyCheckWorker();
      $this->executeBounceWorker();
    } catch(\Exception $e) {
      // continue processing, no need to handle errors
    }
    // if workers took less time to execute than the daemon execution limit,
    // pause daemon execution to ensure that daemon runs only once every X seconds
    $elapsed_time = microtime(true) - $this->timer;
    if($elapsed_time < CronHelper::DAEMON_EXECUTION_LIMIT) {
      $this->pauseExecution(CronHelper::DAEMON_EXECUTION_LIMIT - $elapsed_time);
    }
    // after each execution, re-read daemon data in case it changed
    $daemon = CronHelper::getDaemon();
    if(!$daemon || $daemon['token'] !== $this->token) {
      return $this->terminateRequest();
    }
    return $this->callSelf();
  }

  function pauseExecution($pause_time) {
    return sleep($pause_time);
  }

  function executeScheduleWorker() {
    $scheduler = new SchedulerWorker($this->timer);
    return $scheduler->process();
  }

  function executeQueueWorker() {
    $queue = new SendingQueueWorker($this->timer);
    return $queue->process();
  }

  function executeSendingServiceKeyCheckWorker() {
    $worker = new SendingServiceKeyCheckWorker($this->timer);
    return $worker->process();
  }

  function executePremiumKeyCheckWorker() {
    $worker = new PremiumKeyCheckWorker($this->timer);
    return $worker->process();
  }

  function executeBounceWorker() {
    $bounce = new BounceWorker($this->timer);
    return $bounce->process();
  }

  function callSelf() {
    CronHelper::accessDaemon($this->token, self::REQUEST_TIMEOUT);
    return $this->terminateRequest();
  }

  function abortWithError($message) {
    status_header(404, $message);
    exit;
  }

  function terminateRequest($message = false) {
    die($message);
  }
}