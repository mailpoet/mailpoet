<?php

namespace MailPoet\Cron;

use MailPoet\Cron\Workers\WorkersFactory;

class Daemon {
  public $timer;

  /** @var WorkersFactory */
  private $workers_factory;

  /** @var CronHelper */
  private $cron_helper;

  function __construct(WorkersFactory $workers_factory, CronHelper $cron_helper) {
    $this->timer = microtime(true);
    $this->workers_factory = $workers_factory;
    $this->cron_helper = $cron_helper;
  }

  function run($settings_daemon_data) {
    $settings_daemon_data['run_started_at'] = time();
    $this->cron_helper->saveDaemon($settings_daemon_data);

    $errors = [];
    foreach ($this->getWorkers() as $worker) {
      try {
        $worker->process();
      } catch (\Exception $e) {
        $worker_class_name_parts = explode('\\', get_class($worker));
        $errors[] = [
          'worker' => end($worker_class_name_parts),
          'message' => $e->getMessage(),
        ];
      }
    }

    if (!empty($errors)) {
      $this->cron_helper->saveDaemonLastError($errors);
    }

    // Log successful execution
    $this->cron_helper->saveDaemonRunCompleted(time());
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
    yield $this->workers_factory->createBeamerkWorker($this->timer);
    yield $this->workers_factory->createInactiveSubscribersWorker($this->timer);
    yield $this->workers_factory->createUnsubscribeTokensWorker($this->timer);
    yield $this->workers_factory->createWooCommerceSyncWorker($this->timer);
    yield $this->workers_factory->createAuthorizedSendingEmailsCheckWorker($this->timer);
    yield $this->workers_factory->createWooCommercePastOrdersWorker($this->timer);
    yield $this->workers_factory->createStatsNotificationsWorkerForAutomatedEmails($this->timer);
    yield $this->workers_factory->createSubscriberLinkTokensWorker($this->timer);
  }
}
