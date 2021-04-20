<?php

namespace MailPoet\Cron\Triggers;

use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\AuthorizedSendingEmailsCheck;
use MailPoet\Cron\Workers\Beamer as BeamerWorker;
use MailPoet\Cron\Workers\Bounce as BounceWorker;
use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\Cron\Workers\KeyCheck\PremiumKeyCheck as PremiumKeyCheckWorker;
use MailPoet\Cron\Workers\KeyCheck\SendingServiceKeyCheck as SendingServiceKeyCheckWorker;
use MailPoet\Cron\Workers\Scheduler as SchedulerWorker;
use MailPoet\Cron\Workers\SendingQueue\Migration as MigrationWorker;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Cron\Workers\StatsNotifications\AutomatedEmails;
use MailPoet\Cron\Workers\StatsNotifications\Worker as StatsNotificationsWorker;
use MailPoet\Cron\Workers\SubscriberLinkTokens;
use MailPoet\Cron\Workers\SubscribersEngagementScore;
use MailPoet\Cron\Workers\UnsubscribeTokens;
use MailPoet\Cron\Workers\WooCommercePastOrders;
use MailPoet\Cron\Workers\WooCommerceSync as WooCommerceSyncWorker;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\ScheduledTask;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class WordPress {
  const SCHEDULED_IN_THE_PAST = 'past';
  const SCHEDULED_IN_THE_FUTURE = 'future';

  const RUN_INTERVAL = -1; // seconds
  const LAST_RUN_AT_SETTING = 'cron_trigger_wordpress.last_run_at';

  private $tasksCounts;

  /** @var CronHelper */
  private $cronHelper;

  /** @var MailPoet */
  private $mailpoetTrigger;

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    CronHelper $cronHelper,
    MailPoet $mailpoetTrigger,
    SettingsController $settings,
    WPFunctions $wp
  ) {
    $this->mailpoetTrigger = $mailpoetTrigger;
    $this->settings = $settings;
    $this->wp = $wp;
    $this->cronHelper = $cronHelper;
  }

  public function run() {
    if (!$this->checkRunInterval()) {
      return false;
    }
    return ($this->checkExecutionRequirements()) ?
      $this->mailpoetTrigger->run() :
      self::stop();
  }

  private function checkRunInterval() {
    $lastRunAt = (int)$this->settings->get(self::LAST_RUN_AT_SETTING, 0);
    $runInterval = $this->wp->applyFilters('mailpoet_cron_trigger_wordpress_run_interval', self::RUN_INTERVAL);
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
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);
    $migrationCompletedTasks = $this->getTasksCount([
      'type' => MigrationWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST, self::SCHEDULED_IN_THE_FUTURE],
      'status' => [ScheduledTask::STATUS_COMPLETED],
    ]);
    $migrationFutureTasks = $this->getTasksCount([
      'type' => MigrationWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_FUTURE],
      'status' => [ScheduledTask::STATUS_SCHEDULED],
    ]);
    // sending queue
    $scheduledQueues = SchedulerWorker::getScheduledQueues();
    $runningQueues = SendingQueueWorker::getRunningQueues();
    $sendingLimitReached = MailerLog::isSendingLimitReached();
    $sendingIsPaused = MailerLog::isSendingPaused();
    // sending service
    $mpSendingEnabled = Bridge::isMPSendingServiceEnabled();
    // bounce sync
    $bounceDueTasks = $this->getTasksCount([
      'type' => BounceWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);
    $bounceFutureTasks = $this->getTasksCount([
      'type' => BounceWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_FUTURE],
      'status' => [ScheduledTask::STATUS_SCHEDULED],
    ]);
    // sending service key check
    $msskeycheckDueTasks = $this->getTasksCount([
      'type' => SendingServiceKeyCheckWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);
    $msskeycheckFutureTasks = $this->getTasksCount([
      'type' => SendingServiceKeyCheckWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_FUTURE],
      'status' => [ScheduledTask::STATUS_SCHEDULED],
    ]);
    // premium key check
    $premiumKeySpecified = Bridge::isPremiumKeySpecified();
    $premiumKeycheckDueTasks = $this->getTasksCount([
      'type' => PremiumKeyCheckWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);
    $premiumKeycheckFutureTasks = $this->getTasksCount([
      'type' => PremiumKeyCheckWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_FUTURE],
      'status' => [ScheduledTask::STATUS_SCHEDULED],
    ]);
    // stats notifications
    $statsNotificationsTasks = $this->getTasksCount([
      'type' => StatsNotificationsWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);
    // stats notifications for auto emails
    $autoStatsNotificationsTasks = $this->getTasksCount([
      'type' => AutomatedEmails::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);
    // inactive subscribers check
    $inactiveSubscribersTasks = $this->getTasksCount([
      'type' => InactiveSubscribers::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);
    // unsubscribe tokens check
    $unsubscribeTokensTasks = $this->getTasksCount([
      'type' => UnsubscribeTokens::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);
    // subscriber link tokens check
    $subscriberLinkTokensTasks = $this->getTasksCount([
      'type' => SubscriberLinkTokens::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);
    // WooCommerce sync
    $wooCommerceSyncTasks = $this->getTasksCount([
      'type' => WooCommerceSyncWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);
    // Beamer
    $beamerDueChecks = $this->getTasksCount([
      'type' => BeamerWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);
    $beamerFutureChecks = $this->getTasksCount([
      'type' => BeamerWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_FUTURE],
      'status' => [ScheduledTask::STATUS_SCHEDULED],
    ]);

    // Authorized email addresses check
    $authorizedEmailAddressesTasks = $this->getTasksCount([
      'type' => AuthorizedSendingEmailsCheck::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);

    // WooCommerce past orders revenues sync
    $wooCommercePastOrdersTasks = $this->getTasksCount([
      'type' => WooCommercePastOrders::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);

    // subscriber engagement score
    $subscriberEngagementScoreTasks = $this->getTasksCount([
      'type' => SubscribersEngagementScore::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);

    // check requirements for each worker
    $sendingQueueActive = (($scheduledQueues || $runningQueues) && !$sendingLimitReached && !$sendingIsPaused);
    $bounceSyncActive = ($mpSendingEnabled && ($bounceDueTasks || !$bounceFutureTasks));
    $sendingServiceKeyCheckActive = ($mpSendingEnabled && ($msskeycheckDueTasks || !$msskeycheckFutureTasks));
    $premiumKeyCheckActive = ($premiumKeySpecified && ($premiumKeycheckDueTasks || !$premiumKeycheckFutureTasks));
    $migrationActive = !$migrationDisabled && ($migrationDueTasks || (!$migrationCompletedTasks && !$migrationFutureTasks));
    $beamerActive = $beamerDueChecks || !$beamerFutureChecks;

    return (
      $migrationActive
      || $sendingQueueActive
      || $bounceSyncActive
      || $sendingServiceKeyCheckActive
      || $premiumKeyCheckActive
      || $statsNotificationsTasks
      || $autoStatsNotificationsTasks
      || $inactiveSubscribersTasks
      || $wooCommerceSyncTasks
      || $authorizedEmailAddressesTasks
      || $beamerActive
      || $wooCommercePastOrdersTasks
      || $unsubscribeTokensTasks
      || $subscriberLinkTokensTasks
      || $subscriberEngagementScoreTasks
    );
  }

  public function stop() {
    $cronDaemon = $this->cronHelper->getDaemon();
    if ($cronDaemon) {
      $this->cronHelper->deactivateDaemon($cronDaemon);
    }
  }

  private function loadTasksCounts() {
    $query = sprintf(
      "select
        type,
        status,
        count(*) as count,
        case when scheduled_at <= '%s' then '%s' else '%s' end as scheduled_in
      from %s
      where deleted_at is null
      group by type, status, scheduled_in
      ",
      date('Y-m-d H:i:s', $this->wp->currentTime('timestamp')),
      self::SCHEDULED_IN_THE_PAST,
      self::SCHEDULED_IN_THE_FUTURE,
      ScheduledTask::$_table
    );
    $rows = ScheduledTask::rawQuery($query)->findMany();
    $this->tasksCounts = [];
    foreach ($rows as $r) {
      if (empty($this->tasksCounts[$r->type])) {
        $this->tasksCounts[$r->type] = [];
      }
      if (empty($this->tasksCounts[$r->type][$r->scheduledIn])) {
        $this->tasksCounts[$r->type][$r->scheduledIn] = [];
      }
      $this->tasksCounts[$r->type][$r->scheduledIn][$r->status ?: 'null'] = $r->count;
    }
  }

  private function getTasksCount(array $options) {
    $count = 0;
    $type = $options['type'];
    foreach ($options['scheduled_in'] as $scheduledIn) {
      foreach ($options['status'] as $status) {
        if (! empty($this->tasksCounts[$type][$scheduledIn][$status])) {
          $count += $this->tasksCounts[$type][$scheduledIn][$status];
        }
      }
    }
    return $count;
  }
}
