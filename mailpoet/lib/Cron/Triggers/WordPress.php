<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Cron\Triggers;

use MailPoet\Config\ServicesChecker;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Supervisor;
use MailPoet\Cron\Workers\AuthorizedSendingEmailsCheck;
use MailPoet\Cron\Workers\Beamer as BeamerWorker;
use MailPoet\Cron\Workers\Bounce as BounceWorker;
use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\Cron\Workers\KeyCheck\PremiumKeyCheck as PremiumKeyCheckWorker;
use MailPoet\Cron\Workers\KeyCheck\SendingServiceKeyCheck as SendingServiceKeyCheckWorker;
use MailPoet\Cron\Workers\NewsletterTemplateThumbnails;
use MailPoet\Cron\Workers\ReEngagementEmailsScheduler;
use MailPoet\Cron\Workers\Scheduler as SchedulerWorker;
use MailPoet\Cron\Workers\SendingQueue\Migration as MigrationWorker;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Cron\Workers\StatsNotifications\AutomatedEmails;
use MailPoet\Cron\Workers\StatsNotifications\Worker as StatsNotificationsWorker;
use MailPoet\Cron\Workers\SubscriberLinkTokens;
use MailPoet\Cron\Workers\SubscribersCountCacheRecalculation;
use MailPoet\Cron\Workers\SubscribersEmailCount;
use MailPoet\Cron\Workers\SubscribersEngagementScore;
use MailPoet\Cron\Workers\SubscribersLastEngagement;
use MailPoet\Cron\Workers\SubscribersStatsReport;
use MailPoet\Cron\Workers\UnsubscribeTokens;
use MailPoet\Cron\Workers\WooCommercePastOrders;
use MailPoet\Cron\Workers\WooCommerceSync as WooCommerceSyncWorker;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Mailer\MailerLog;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class WordPress {
  const SCHEDULED_IN_THE_PAST = 'past';
  const SCHEDULED_IN_THE_FUTURE = 'future';

  const RUN_INTERVAL = -1; // seconds
  const LAST_RUN_AT_SETTING = 'cron_trigger_wordpress.last_run_at';

  private $tasksCounts;

  /** @var CronHelper */
  private $cronHelper;

  /** @var Supervisor  */
  private $supervisor;

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  /** @var ServicesChecker */
  private $serviceChecker;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var EntityManager */
  private $entityManager;

  public function __construct(
    CronHelper $cronHelper,
    Supervisor $supervisor,
    SettingsController $settings,
    ServicesChecker $serviceChecker,
    WPFunctions $wp,
    ScheduledTasksRepository $scheduledTasksRepository,
    EntityManager $entityManager
  ) {
    $this->supervisor = $supervisor;
    $this->settings = $settings;
    $this->wp = $wp;
    $this->cronHelper = $cronHelper;
    $this->serviceChecker = $serviceChecker;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->entityManager = $entityManager;
  }

  public function run() {
    if (!$this->checkRunInterval()) {
      return false;
    }
    if (!$this->checkExecutionRequirements()) {
      $this->stop();
      return;
    }

    $this->supervisor->init();
    return $this->supervisor->checkDaemon();
  }

  private function checkRunInterval() {
    $runInterval = $this->wp->applyFilters('mailpoet_cron_trigger_wordpress_run_interval', self::RUN_INTERVAL);
    if ($runInterval === -1) {
      return true;
    }
    $lastRunAt = (int)$this->settings->get(self::LAST_RUN_AT_SETTING, 0);
    $runIntervalElapsed = (time() - $lastRunAt) >= $runInterval;
    if ($runIntervalElapsed) {
      $this->settings->set(self::LAST_RUN_AT_SETTING, time());
      return true;
    }
    return false;
  }

  public static function resetRunInterval() {
    $settings = SettingsController::getInstance();
    $settings->set(self::LAST_RUN_AT_SETTING, 0);
  }

  public function checkExecutionRequirements() {
    $this->loadTasksCounts();

    // migration
    $migrationDisabled = $this->settings->get('cron_trigger.method') === 'none';
    $migrationDueTasks = $this->getTasksCount([
      'type' => MigrationWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTaskEntity::STATUS_SCHEDULED],
    ]);
    $migrationCompletedTasks = $this->getTasksCount([
      'type' => MigrationWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST, self::SCHEDULED_IN_THE_FUTURE],
      'status' => [ScheduledTaskEntity::STATUS_COMPLETED],
    ]);
    $migrationFutureTasks = $this->getTasksCount([
      'type' => MigrationWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_FUTURE],
      'status' => [ScheduledTaskEntity::STATUS_SCHEDULED],
    ]);
    // sending queue
    $scheduledQueues = $this->scheduledTasksRepository->findScheduledSendingTasks(SchedulerWorker::TASK_BATCH_SIZE);
    $runningQueues = $this->scheduledTasksRepository->findRunningSendingTasks(SendingQueueWorker::TASK_BATCH_SIZE);
    $sendingLimitReached = MailerLog::isSendingLimitReached();
    $sendingIsPaused = MailerLog::isSendingPaused();
    $sendingWaitingForRetry = MailerLog::isSendingWaitingForRetry();
    // sending service
    $mpSendingEnabled = Bridge::isMPSendingServiceEnabled();
    // bounce sync
    $bounceDueTasks = $this->getTasksCount([
      'type' => BounceWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTaskEntity::STATUS_SCHEDULED],
    ]);
    $bounceFutureTasks = $this->getTasksCount([
      'type' => BounceWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_FUTURE],
      'status' => [ScheduledTaskEntity::STATUS_SCHEDULED],
    ]);
    // sending service key check
    $msskeycheckDueTasks = $this->getTasksCount([
      'type' => SendingServiceKeyCheckWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTaskEntity::STATUS_SCHEDULED],
    ]);
    $msskeycheckFutureTasks = $this->getTasksCount([
      'type' => SendingServiceKeyCheckWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_FUTURE],
      'status' => [ScheduledTaskEntity::STATUS_SCHEDULED],
    ]);
    // premium key check
    $premiumKeySpecified = Bridge::isPremiumKeySpecified();
    $premiumKeycheckDueTasks = $this->getTasksCount([
      'type' => PremiumKeyCheckWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTaskEntity::STATUS_SCHEDULED],
    ]);
    $premiumKeycheckFutureTasks = $this->getTasksCount([
      'type' => PremiumKeyCheckWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_FUTURE],
      'status' => [ScheduledTaskEntity::STATUS_SCHEDULED],
    ]);
    // subscriber stats
    $isAnyKeyValid = $this->serviceChecker->getAnyValidKey();
    $statsReportDueTasks = $this->getTasksCount([
      'type' => SubscribersStatsReport::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTaskEntity::STATUS_SCHEDULED],
    ]);
    $statsReportFutureTasks = $this->getTasksCount([
      'type' => SubscribersStatsReport::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_FUTURE],
      'status' => [ScheduledTaskEntity::STATUS_SCHEDULED],
    ]);
    // stats notifications
    $statsNotificationsTasks = $this->getTasksCount([
      'type' => StatsNotificationsWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTaskEntity::STATUS_SCHEDULED],
    ]);
    // stats notifications for auto emails
    $autoStatsNotificationsTasks = $this->getTasksCount([
      'type' => AutomatedEmails::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTaskEntity::STATUS_SCHEDULED],
    ]);
    // subscribers emails count
    $subscribersEmailsCount = $this->getTasksCount([
      'type' => SubscribersEmailCount::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTaskEntity::STATUS_SCHEDULED],
    ]);
    // inactive subscribers check
    $inactiveSubscribersTasks = $this->getTasksCount([
      'type' => InactiveSubscribers::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTaskEntity::STATUS_SCHEDULED],
    ]);
    // unsubscribe tokens check
    $unsubscribeTokensTasks = $this->getTasksCount([
      'type' => UnsubscribeTokens::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTaskEntity::STATUS_SCHEDULED],
    ]);
    // subscriber link tokens check
    $subscriberLinkTokensTasks = $this->getTasksCount([
      'type' => SubscriberLinkTokens::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTaskEntity::STATUS_SCHEDULED],
    ]);
    // WooCommerce sync
    $wooCommerceSyncTasks = $this->getTasksCount([
      'type' => WooCommerceSyncWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTaskEntity::STATUS_SCHEDULED],
    ]);
    // Beamer
    $beamerDueChecks = $this->getTasksCount([
      'type' => BeamerWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTaskEntity::STATUS_SCHEDULED],
    ]);
    $beamerFutureChecks = $this->getTasksCount([
      'type' => BeamerWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_FUTURE],
      'status' => [ScheduledTaskEntity::STATUS_SCHEDULED],
    ]);

    // Authorized email addresses check
    $authorizedEmailAddressesTasks = $this->getTasksCount([
      'type' => AuthorizedSendingEmailsCheck::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTaskEntity::STATUS_SCHEDULED],
    ]);

    // WooCommerce past orders revenues sync
    $wooCommercePastOrdersTasks = $this->getTasksCount([
      'type' => WooCommercePastOrders::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTaskEntity::STATUS_SCHEDULED],
    ]);

    // subscriber engagement score
    $subscriberEngagementScoreTasks = $this->getTasksCount([
      'type' => SubscribersEngagementScore::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTaskEntity::STATUS_SCHEDULED],
    ]);

    // subscriber counts cache recalculation
    $subscribersCountCacheRecalculationTasks = $this->getTasksCount([
      'type' => SubscribersCountCacheRecalculation::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTaskEntity::STATUS_SCHEDULED],
    ]);

    // subscriber last engagement
    $subscribersLastEngagementTasks = $this->getTasksCount([
      'type' => SubscribersLastEngagement::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTaskEntity::STATUS_SCHEDULED],
    ]);

    // re-engagement emails scheduling;
    $subscribersReEngagementSchedulingTasks = $this->getTasksCount([
      'type' => ReEngagementEmailsScheduler::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTaskEntity::STATUS_SCHEDULED],
    ]);

    // newsletter template thumbnails
    $newsletterTemplateThumbnailsTasks = $this->getTasksCount([
      'type' => NewsletterTemplateThumbnails::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTaskEntity::STATUS_SCHEDULED],
    ]);

    // check requirements for each worker
    $sendingQueueActive = (($scheduledQueues || $runningQueues) && !$sendingLimitReached && !$sendingIsPaused && !$sendingWaitingForRetry);
    $bounceSyncActive = ($mpSendingEnabled && ($bounceDueTasks || !$bounceFutureTasks));
    $sendingServiceKeyCheckActive = ($mpSendingEnabled && ($msskeycheckDueTasks || !$msskeycheckFutureTasks));
    $premiumKeyCheckActive = ($premiumKeySpecified && ($premiumKeycheckDueTasks || !$premiumKeycheckFutureTasks));
    $subscribersStatsReportActive = ($isAnyKeyValid && ($statsReportDueTasks || !$statsReportFutureTasks));
    $migrationActive = !$migrationDisabled && ($migrationDueTasks || (!$migrationCompletedTasks && !$migrationFutureTasks));
    $beamerActive = $beamerDueChecks || !$beamerFutureChecks;

    return (
      $migrationActive
      || $sendingQueueActive
      || $bounceSyncActive
      || $sendingServiceKeyCheckActive
      || $premiumKeyCheckActive
      || $subscribersStatsReportActive
      || $statsNotificationsTasks
      || $autoStatsNotificationsTasks
      || $subscribersEmailsCount
      || $inactiveSubscribersTasks
      || $wooCommerceSyncTasks
      || $authorizedEmailAddressesTasks
      || $beamerActive
      || $wooCommercePastOrdersTasks
      || $unsubscribeTokensTasks
      || $subscriberLinkTokensTasks
      || $subscriberEngagementScoreTasks
      || $subscribersCountCacheRecalculationTasks
      || $subscribersLastEngagementTasks
      || $subscribersReEngagementSchedulingTasks
      || $newsletterTemplateThumbnailsTasks
    );
  }

  public function stop() {
    $cronDaemon = $this->cronHelper->getDaemon();
    if ($cronDaemon) {
      $this->cronHelper->deactivateDaemon($cronDaemon);
    }
  }

  private function loadTasksCounts() {
    $scheduledTasksTableName = $this->entityManager->getClassMetadata(ScheduledTaskEntity::class)->getTableName();
    $sql = "
      select
        type,
        status,
        count(*) as count,
        case when scheduled_at <= :now then :past else :future end as scheduled_in
      from $scheduledTasksTableName
      where deleted_at is null AND (status != :statusCompleted OR status IS NULL OR `type` = :typeMigration)
      group by type, status, scheduled_in";

    $stmt = $this->entityManager->getConnection()->prepare($sql);
    $stmt->bindValue('now', date('Y-m-d H:i:s', $this->wp->currentTime('timestamp')));
    $stmt->bindValue('past', self::SCHEDULED_IN_THE_PAST);
    $stmt->bindValue('future', self::SCHEDULED_IN_THE_FUTURE);
    $stmt->bindValue('statusCompleted', ScheduledTaskEntity::STATUS_COMPLETED);
    $stmt->bindValue('typeMigration', MigrationWorker::TASK_TYPE);
    $rows = $stmt->executeQuery()->fetchAllAssociative();

    $this->tasksCounts = [];
    foreach ($rows as $r) {
      if (empty($this->tasksCounts[$r['type']])) {
        $this->tasksCounts[$r['type']] = [];
      }
      if (empty($this->tasksCounts[$r['type']][$r['scheduled_in']])) {
        $this->tasksCounts[$r['type']][$r['scheduled_in']] = [];
      }
      $this->tasksCounts[$r['type']][$r['scheduled_in']][$r['status'] ?: 'null'] = $r['count'];
    }
  }

  private function getTasksCount(array $options) {
    $count = 0;
    $type = $options['type'];
    foreach ($options['scheduled_in'] as $scheduledIn) {
      foreach ($options['status'] as $status) {
        if (!empty($this->tasksCounts[$type][$scheduledIn][$status])) {
          $count += $this->tasksCounts[$type][$scheduledIn][$status];
        }
      }
    }
    return $count;
  }
}
