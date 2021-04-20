<?php

namespace MailPoet\Cron;

use MailPoet\Cron\Workers\WorkersFactory;

class Daemon {
  public $timer;

  /** @var CronHelper */
  private $cronHelper;

  /** @var CronWorkerRunner */
  private $cronWorkerRunner;

  /** @var WorkersFactory */
  private $workersFactory;

  public function __construct(
    CronHelper $cronHelper,
    CronWorkerRunner $cronWorkerRunner,
    WorkersFactory $workersFactory
  ) {
    $this->timer = microtime(true);
    $this->workersFactory = $workersFactory;
    $this->cronWorkerRunner = $cronWorkerRunner;
    $this->cronHelper = $cronHelper;
  }

  public function run($settingsDaemonData) {
    $settingsDaemonData['run_started_at'] = time();
    $this->cronHelper->saveDaemon($settingsDaemonData);

    $errors = [];
    foreach ($this->getWorkers() as $worker) {
      try {
        if ($worker instanceof CronWorkerInterface) {
          $this->cronWorkerRunner->run($worker);
        } else {
          $worker->process($this->timer); // BC for workers not implementing CronWorkerInterface
        }
      } catch (\Exception $e) {
        $workerClassNameParts = explode('\\', get_class($worker));
        $errors[] = [
          'worker' => end($workerClassNameParts),
          'message' => $e->getMessage(),
        ];
      }
    }

    if (!empty($errors)) {
      $this->cronHelper->saveDaemonLastError($errors);
    }

    // Log successful execution
    $this->cronHelper->saveDaemonRunCompleted(time());
  }

  private function getWorkers() {
    yield $this->workersFactory->createMigrationWorker();
    yield $this->workersFactory->createStatsNotificationsWorker(); // not CronWorkerInterface compatible
    yield $this->workersFactory->createScheduleWorker(); // not CronWorkerInterface compatible
    yield $this->workersFactory->createQueueWorker(); // not CronWorkerInterface compatible
    yield $this->workersFactory->createSendingServiceKeyCheckWorker();
    yield $this->workersFactory->createPremiumKeyCheckWorker();
    yield $this->workersFactory->createBounceWorker();
    yield $this->workersFactory->createExportFilesCleanupWorker();
    yield $this->workersFactory->createBeamerkWorker();
    yield $this->workersFactory->createInactiveSubscribersWorker();
    yield $this->workersFactory->createUnsubscribeTokensWorker();
    yield $this->workersFactory->createWooCommerceSyncWorker();
    yield $this->workersFactory->createAuthorizedSendingEmailsCheckWorker();
    yield $this->workersFactory->createWooCommercePastOrdersWorker();
    yield $this->workersFactory->createStatsNotificationsWorkerForAutomatedEmails();
    yield $this->workersFactory->createSubscriberLinkTokensWorker();
    yield $this->workersFactory->createSubscribersEngagementScoreWorker();
  }
}
