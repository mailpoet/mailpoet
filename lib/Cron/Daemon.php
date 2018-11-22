<?php
namespace MailPoet\Cron;
use MailPoet\Cron\Workers\Scheduler as SchedulerWorker;
use MailPoet\Cron\Workers\SendingQueue\Migration as MigrationWorker;
use MailPoet\Cron\Workers\SendingQueue\SendingErrorHandler;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Cron\Workers\Bounce as BounceWorker;
use MailPoet\Cron\Workers\KeyCheck\PremiumKeyCheck as PremiumKeyCheckWorker;
use MailPoet\Cron\Workers\KeyCheck\SendingServiceKeyCheck as SendingServiceKeyCheckWorker;

if(!defined('ABSPATH')) exit;

class Daemon {
  public $timer;

  function __construct() {
    $this->timer = microtime(true);
  }

  function run($settings_daemon_data) {
    $settings_daemon_data['run_started_at'] = time();
    CronHelper::saveDaemon($settings_daemon_data);
    try {
      $this->executeMigrationWorker();
      $this->executeScheduleWorker();
      $this->executeQueueWorker();
      $this->executeSendingServiceKeyCheckWorker();
      $this->executePremiumKeyCheckWorker();
      $this->executeBounceWorker();
    } catch(\Exception $e) {
      CronHelper::saveDaemonLastError($e->getMessage());
    }
    // Log successful execution
    CronHelper::saveDaemonRunCompleted(time());
  }

  function executeScheduleWorker() {
    $scheduler = new SchedulerWorker($this->timer);
    return $scheduler->process();
  }

  function executeQueueWorker() {
    $queue = new SendingQueueWorker(new SendingErrorHandler(), $this->timer);
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

  function executeMigrationWorker() {
    $migration = new MigrationWorker($this->timer);
    return $migration->process();
  }

}
