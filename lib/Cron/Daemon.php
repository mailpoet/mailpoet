<?php
namespace MailPoet\Cron;

use MailPoet\Cron\Workers\WorkersFactory;
use MailPoet\Settings\SettingsController;

if (!defined('ABSPATH')) exit;

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

    foreach ($this->getWorkers() as $worker) {
      try {
        $worker->process();
      } catch (\Exception $e) {
        CronHelper::saveDaemonLastError($e->getMessage());
      }
    }

    // Log successful execution
    CronHelper::saveDaemonRunCompleted(time());
  }

  private function getWorkers() {
    yield $this->workers_factory->createMigrationWorker($this->timer);
    yield $this->workers_factory->createStatsNotificationsWorker($this->timer);
    yield $this->workers_factory->createScheduleWorker($this->timer);
    yield $this->workers_factory->createQueueWorker($this->timer);
    yield $this->workers_factory->createSendingServiceKeyCheckWorker($this->timer);
    yield $this->workers_factory->createPremiumKeyCheckWorker($this->timer);
    yield $this->workers_factory->createBounceWorker($this->timer);
    yield $this->workers_factory->createExportFilesCleanupWorker($this->timer);
    yield $this->workers_factory->createInactiveSubscribersWorker($this->timer);
    $settings = new SettingsController();
    if ($settings->get('woo_commerce_list_sync_enabled')) {
      yield $this->workers_factory->createWooCommerceSyncWorker($this->timer);
    }
  }
}
