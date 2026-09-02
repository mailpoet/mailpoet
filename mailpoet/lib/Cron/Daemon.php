<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Cron;

use MailPoet\Cron\Workers\WorkersFactory;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Mailer\SendingLimitReachedException;
use MailPoet\Util\Helpers;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class Daemon {
  public $timer;

  /** @var CronHelper */
  private $cronHelper;

  /** @var CronWorkerRunner */
  private $cronWorkerRunner;

  /** @var EntityManager */
  private $entityManager;

  /** @var WorkersFactory */
  private $workersFactory;

  /** @var LoggerFactory  */
  private $loggerFactory;

  public function __construct(
    CronHelper $cronHelper,
    CronWorkerRunner $cronWorkerRunner,
    EntityManager $entityManager,
    WorkersFactory $workersFactory,
    LoggerFactory $loggerFactory
  ) {
    $this->timer = microtime(true);
    $this->workersFactory = $workersFactory;
    $this->cronWorkerRunner = $cronWorkerRunner;
    $this->entityManager = $entityManager;
    $this->cronHelper = $cronHelper;
    $this->loggerFactory = $loggerFactory;
  }

  public function run($settingsDaemonData) {
    $settingsDaemonData['run_started_at'] = time();
    $this->cronHelper->saveDaemon($settingsDaemonData);

    $errors = [];
    foreach ($this->getWorkers() as $createWorker) {
      if (wp_is_maintenance_mode()) {
        // stop execution when in maintenance mode
        break;
      }

      $workerName = 'unknown (worker could not be created)';
      try {
        // Clear the entity manager memory for every cron run.
        // This avoids using stale data and prevents memory leaks.
        $this->entityManager->clear();

        // Built inside the try on purpose. A worker that throws while being
        // constructed used to escape this loop and abort the whole daemon run.
        $worker = $createWorker();
        $workerName = $this->getWorkerName($worker);

        if ($worker instanceof CronWorkerInterface) {
          $this->cronWorkerRunner->run($worker);
        } else {
          $worker->process($this->timer); // BC for workers not implementing CronWorkerInterface
        }
      } catch (\Exception $e) {
        Helpers::mySqlGoneAwayExceptionHandler($e);

        // Expected sending state, not an error — sending resumes once the frequency interval passes.
        if ($e instanceof SendingLimitReachedException) {
          $this->loggerFactory->getLogger(LoggerFactory::TOPIC_CRON)->info($e->getMessage(), ['worker' => $workerName]);
          continue;
        }

        $errors[] = [
          'worker' => $workerName,
          'message' => $e->getMessage(),
        ];

        if ($e->getCode() === CronHelper::DAEMON_EXECUTION_LIMIT_REACHED) {
          break;
        }

        $this->loggerFactory->getLogger(LoggerFactory::TOPIC_CRON)->error($e->getMessage(), ['error' => $e, 'worker' => $workerName]);
      }
    }

    if (!empty($errors)) {
      $this->cronHelper->saveDaemonLastError($errors);
    }

    // Log successful execution
    $this->cronHelper->saveDaemonRunCompleted(time());
  }

  /**
   * @param mixed $worker
   */
  private function getWorkerName($worker): string {
    if (!is_object($worker)) {
      return '';
    }
    $workerClassNameParts = explode('\\', get_class($worker));
    return (string)end($workerClassNameParts);
  }

  /**
   * Each worker is yielded as a factory rather than an instance, so that the
   * cost and the risk of building it land inside run()'s try/catch.
   *
   * @return \Generator<int, callable, mixed, void>
   */
  private function getWorkers(): \Generator {
    yield fn() => $this->workersFactory->createStatsNotificationsWorker(); // not CronWorkerInterface compatible
    yield fn() => $this->workersFactory->createScheduleWorker(); // not CronWorkerInterface compatible
    yield fn() => $this->workersFactory->createQueueWorker(); // not CronWorkerInterface compatible
    yield fn() => $this->workersFactory->createSendingServiceKeyCheckWorker();
    yield fn() => $this->workersFactory->createPremiumKeyCheckWorker();
    yield fn() => $this->workersFactory->createSubscribersStatsReportWorker();
    yield fn() => $this->workersFactory->createBounceWorker();
    yield fn() => $this->workersFactory->createExportFilesCleanupWorker();
    yield fn() => $this->workersFactory->createLogCleanupWorker();
    yield fn() => $this->workersFactory->createSendingTaskSubscribersCleanupWorker();
    yield fn() => $this->workersFactory->createBounceTaskSubscribersCleanupWorker();
    yield fn() => $this->workersFactory->createSendingQueueBodyCleanupWorker();
    yield fn() => $this->workersFactory->createInactiveSubscribersMaintenanceWorker();
    yield fn() => $this->workersFactory->createUnconfirmedSubscribersCleanupWorker();
    yield fn() => $this->workersFactory->createUnsubscribeTokensWorker();
    yield fn() => $this->workersFactory->createWooCommerceSyncWorker();
    yield fn() => $this->workersFactory->createAuthorizedSendingEmailsCheckWorker();
    yield fn() => $this->workersFactory->createWooCommercePastOrdersWorker();
    yield fn() => $this->workersFactory->createStatsNotificationsWorkerForAutomatedEmails();
    yield fn() => $this->workersFactory->createSubscriberLinkTokensWorker();
    yield fn() => $this->workersFactory->createSubscribersLastEngagementWorker();
    yield fn() => $this->workersFactory->createSubscribersCountCacheRecalculationWorker();
    yield fn() => $this->workersFactory->createReEngagementEmailsSchedulerWorker();
    yield fn() => $this->workersFactory->createNewsletterTemplateThumbnailsWorker();
    yield fn() => $this->workersFactory->createAbandonedCartWorker();
    yield fn() => $this->workersFactory->createBackfillEngagementDataWorker();
    yield fn() => $this->workersFactory->createSubscribersSegmentsCountSyncWorker();
    yield fn() => $this->workersFactory->createMixpanelWorker();
    yield fn() => $this->workersFactory->createTracksWorker();
    yield fn() => $this->workersFactory->createStatisticsExportWorker();
    yield fn() => $this->workersFactory->createBulkConfirmationEmailResendWorker();
    yield fn() => $this->workersFactory->createSubscriberLimitNotificationWorker();
    yield fn() => $this->workersFactory->createSubscribersEngagementScoreWorker();
  }
}
