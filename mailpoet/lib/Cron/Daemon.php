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
    foreach ($this->getWorkers() as $factoryMethod => $createWorker) {
      if (wp_is_maintenance_mode()) {
        // stop execution when in maintenance mode
        break;
      }

      $workerName = $factoryMethod;
      try {
        // Clear the entity manager memory for every cron run.
        // This avoids using stale data and prevents memory leaks.
        $this->entityManager->clear();

        // Built inside the try on purpose. A worker that throws while being
        // constructed used to escape this loop and abort the whole daemon run.
        $worker = $createWorker();
        $workerName = $this->getWorkerName($worker) ?: $factoryMethod;

        if ($worker instanceof CronWorkerInterface) {
          $this->cronWorkerRunner->run($worker);
        } else {
          $worker->process($this->timer); // BC for workers not implementing CronWorkerInterface
        }
      } catch (\Throwable $e) {
        // Throwable, not Exception: a worker that fails to build usually fails with
        // an Error (a type mismatch or a missing class), which is the whole reason
        // construction moved inside this try.
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
   * cost and the risk of building it land inside run()'s try/catch. The key is
   * the factory method, which is the only name available when a worker throws
   * before there is an instance to read a class off.
   *
   * @return \Generator<string, callable, mixed, void>
   */
  private function getWorkers(): \Generator {
    yield 'createStatsNotificationsWorker' => fn() => $this->workersFactory->createStatsNotificationsWorker(); // not CronWorkerInterface compatible
    yield 'createScheduleWorker' => fn() => $this->workersFactory->createScheduleWorker(); // not CronWorkerInterface compatible
    yield 'createQueueWorker' => fn() => $this->workersFactory->createQueueWorker(); // not CronWorkerInterface compatible
    yield 'createSendingServiceKeyCheckWorker' => fn() => $this->workersFactory->createSendingServiceKeyCheckWorker();
    yield 'createPremiumKeyCheckWorker' => fn() => $this->workersFactory->createPremiumKeyCheckWorker();
    yield 'createSubscribersStatsReportWorker' => fn() => $this->workersFactory->createSubscribersStatsReportWorker();
    yield 'createBounceWorker' => fn() => $this->workersFactory->createBounceWorker();
    yield 'createExportFilesCleanupWorker' => fn() => $this->workersFactory->createExportFilesCleanupWorker();
    yield 'createLogCleanupWorker' => fn() => $this->workersFactory->createLogCleanupWorker();
    yield 'createSendingTaskSubscribersCleanupWorker' => fn() => $this->workersFactory->createSendingTaskSubscribersCleanupWorker();
    yield 'createBounceTaskSubscribersCleanupWorker' => fn() => $this->workersFactory->createBounceTaskSubscribersCleanupWorker();
    yield 'createSendingQueueBodyCleanupWorker' => fn() => $this->workersFactory->createSendingQueueBodyCleanupWorker();
    yield 'createInactiveSubscribersMaintenanceWorker' => fn() => $this->workersFactory->createInactiveSubscribersMaintenanceWorker();
    yield 'createUnconfirmedSubscribersCleanupWorker' => fn() => $this->workersFactory->createUnconfirmedSubscribersCleanupWorker();
    yield 'createUnsubscribeTokensWorker' => fn() => $this->workersFactory->createUnsubscribeTokensWorker();
    yield 'createWooCommerceSyncWorker' => fn() => $this->workersFactory->createWooCommerceSyncWorker();
    yield 'createAuthorizedSendingEmailsCheckWorker' => fn() => $this->workersFactory->createAuthorizedSendingEmailsCheckWorker();
    yield 'createWooCommercePastOrdersWorker' => fn() => $this->workersFactory->createWooCommercePastOrdersWorker();
    yield 'createStatsNotificationsWorkerForAutomatedEmails' => fn() => $this->workersFactory->createStatsNotificationsWorkerForAutomatedEmails();
    yield 'createSubscriberLinkTokensWorker' => fn() => $this->workersFactory->createSubscriberLinkTokensWorker();
    yield 'createSubscribersLastEngagementWorker' => fn() => $this->workersFactory->createSubscribersLastEngagementWorker();
    yield 'createSubscribersCountCacheRecalculationWorker' => fn() => $this->workersFactory->createSubscribersCountCacheRecalculationWorker();
    yield 'createReEngagementEmailsSchedulerWorker' => fn() => $this->workersFactory->createReEngagementEmailsSchedulerWorker();
    yield 'createNewsletterTemplateThumbnailsWorker' => fn() => $this->workersFactory->createNewsletterTemplateThumbnailsWorker();
    yield 'createAbandonedCartWorker' => fn() => $this->workersFactory->createAbandonedCartWorker();
    yield 'createBackfillEngagementDataWorker' => fn() => $this->workersFactory->createBackfillEngagementDataWorker();
    yield 'createSubscribersSegmentsCountSyncWorker' => fn() => $this->workersFactory->createSubscribersSegmentsCountSyncWorker();
    yield 'createMixpanelWorker' => fn() => $this->workersFactory->createMixpanelWorker();
    yield 'createTracksWorker' => fn() => $this->workersFactory->createTracksWorker();
    yield 'createStatisticsExportWorker' => fn() => $this->workersFactory->createStatisticsExportWorker();
    yield 'createBulkConfirmationEmailResendWorker' => fn() => $this->workersFactory->createBulkConfirmationEmailResendWorker();
    yield 'createSubscriberLimitNotificationWorker' => fn() => $this->workersFactory->createSubscriberLimitNotificationWorker();
    yield 'createSubscribersEngagementScoreWorker' => fn() => $this->workersFactory->createSubscribersEngagementScoreWorker();
  }
}
