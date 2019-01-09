<?php
namespace MailPoet\Cron;

use MailPoet\Cron\Workers\WorkersFactory;

if(!defined('ABSPATH')) exit;

class Daemon {
  public $timer;

  /** @var WorkersFactory */
  private $workers_factory;

  function __construct(WorkersFactory $workers_factory) {
    $this->timer = microtime(true);
    $this->workers_factory = $workers_factory;
  }

  function run($settings_daemon_data) {
    $settings_daemon_data['run_started_at'] = time();
    CronHelper::saveDaemon($settings_daemon_data);
    try {
      $this->executeMigrationWorker();
      $this->executeScheduleWorker();
      $this->executeQueueWorker();
      $this->executeStatsNotificationsWorker();
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
    $scheduler = $this->workers_factory->createScheduleWorker($this->timer);
    return $scheduler->process();
  }

  function executeQueueWorker() {
    $queue = $this->workers_factory->createQueueWorker($this->timer);
    return $queue->process();
  }

  function executeStatsNotificationsWorker() {
    $worker = $this->workers_factory->createStatsNotificationsWorker($this->timer);
    return $worker->process();
  }

  function executeSendingServiceKeyCheckWorker() {
    $worker = $this->workers_factory->createSendingServiceKeyCheckWorker($this->timer);
    return $worker->process();
  }

  function executePremiumKeyCheckWorker() {
    $worker = $this->workers_factory->createPremiumKeyCheckWorker($this->timer);
    return $worker->process();
  }

  function executeBounceWorker() {
    $bounce = $this->workers_factory->createBounceWorker($this->timer);
    return $bounce->process();
  }

  function executeMigrationWorker() {
    $migration = $this->workers_factory->createMigrationWorker($this->timer);
    return $migration->process();
  }

}
