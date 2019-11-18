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

  private $tasks_counts;

  /** @var CronHelper */
  private $cron_helper;

  /** @var MailPoet */
  private $mailpoet_trigger;

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  function __construct(
    CronHelper $cron_helper,
    MailPoet $mailpoet_trigger,
    SettingsController $settings,
    WPFunctions $wp
  ) {
    $this->mailpoet_trigger = $mailpoet_trigger;
    $this->settings = $settings;
    $this->wp = $wp;
    $this->cron_helper = $cron_helper;
  }

  function run() {
    if (!$this->checkRunInterval()) {
      return false;
    }
    return ($this->checkExecutionRequirements()) ?
      $this->mailpoet_trigger->run() :
      self::stop();
  }

  private function checkRunInterval() {
    $last_run_at = (int)$this->settings->get(self::LAST_RUN_AT_SETTING, 0);
    $run_interval = $this->wp->applyFilters('mailpoet_cron_trigger_wordpress_run_interval', self::RUN_INTERVAL);
    $run_interval_elapsed = (time() - $last_run_at) >= $run_interval;
    if ($run_interval_elapsed) {
      $this->settings->set(self::LAST_RUN_AT_SETTING, time());
      return true;
    }
    return false;
  }

  static function resetRunInterval() {
    $settings = SettingsController::getInstance();
    $settings->set(self::LAST_RUN_AT_SETTING, 0);
  }

  function checkExecutionRequirements() {
    $this->loadTasksCounts();

    // migration
    $migration_disabled = $this->settings->get('cron_trigger.method') === 'none';
    $migration_due_tasks = $this->getTasksCount([
      'type' => MigrationWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);
    $migration_completed_tasks = $this->getTasksCount([
      'type' => MigrationWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST, self::SCHEDULED_IN_THE_FUTURE],
      'status' => [ScheduledTask::STATUS_COMPLETED],
    ]);
    $migration_future_tasks = $this->getTasksCount([
      'type' => MigrationWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_FUTURE],
      'status' => [ScheduledTask::STATUS_SCHEDULED],
    ]);
    // sending queue
    $scheduled_queues = SchedulerWorker::getScheduledQueues();
    $running_queues = SendingQueueWorker::getRunningQueues();
    $sending_limit_reached = MailerLog::isSendingLimitReached();
    $sending_is_paused = MailerLog::isSendingPaused();
    // sending service
    $mp_sending_enabled = Bridge::isMPSendingServiceEnabled();
    // bounce sync
    $bounce_due_tasks = $this->getTasksCount([
      'type' => BounceWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);
    $bounce_future_tasks = $this->getTasksCount([
      'type' => BounceWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_FUTURE],
      'status' => [ScheduledTask::STATUS_SCHEDULED],
    ]);
    // sending service key check
    $msskeycheck_due_tasks = $this->getTasksCount([
      'type' => SendingServiceKeyCheckWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);
    $msskeycheck_future_tasks = $this->getTasksCount([
      'type' => SendingServiceKeyCheckWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_FUTURE],
      'status' => [ScheduledTask::STATUS_SCHEDULED],
    ]);
    // premium key check
    $premium_key_specified = Bridge::isPremiumKeySpecified();
    $premium_keycheck_due_tasks = $this->getTasksCount([
      'type' => PremiumKeyCheckWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);
    $premium_keycheck_future_tasks = $this->getTasksCount([
      'type' => PremiumKeyCheckWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_FUTURE],
      'status' => [ScheduledTask::STATUS_SCHEDULED],
    ]);
    // stats notifications
    $stats_notifications_tasks = $this->getTasksCount([
      'type' => StatsNotificationsWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);
    // stats notifications for auto emails
    $auto_stats_notifications_tasks = $this->getTasksCount([
      'type' => AutomatedEmails::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);
    // inactive subscribers check
    $inactive_subscribers_tasks = $this->getTasksCount([
      'type' => InactiveSubscribers::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);
    // unsubscribe tokens check
    $unsubscribe_tokens_tasks = $this->getTasksCount([
      'type' => UnsubscribeTokens::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);
    // subscriber link tokens check
    $subscriber_link_tokens_tasks = $this->getTasksCount([
      'type' => SubscriberLinkTokens::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);
    // WooCommerce sync
    $woo_commerce_sync_tasks = $this->getTasksCount([
      'type' => WooCommerceSyncWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);
    // Beamer
    $beamer_due_checks = $this->getTasksCount([
      'type' => BeamerWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);
    $beamer_future_checks = $this->getTasksCount([
      'type' => BeamerWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_FUTURE],
      'status' => [ScheduledTask::STATUS_SCHEDULED],
    ]);

    // Authorized email addresses check
    $authorized_email_addresses_tasks = $this->getTasksCount([
      'type' => AuthorizedSendingEmailsCheck::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);

    // WooCommerce past orders revenues sync
    $woo_commerce_past_orders_tasks = $this->getTasksCount([
      'type' => WooCommercePastOrders::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED],
    ]);

    // check requirements for each worker
    $sending_queue_active = (($scheduled_queues || $running_queues) && !$sending_limit_reached && !$sending_is_paused);
    $bounce_sync_active = ($mp_sending_enabled && ($bounce_due_tasks || !$bounce_future_tasks));
    $sending_service_key_check_active = ($mp_sending_enabled && ($msskeycheck_due_tasks || !$msskeycheck_future_tasks));
    $premium_key_check_active = ($premium_key_specified && ($premium_keycheck_due_tasks || !$premium_keycheck_future_tasks));
    $migration_active = !$migration_disabled && ($migration_due_tasks || (!$migration_completed_tasks && !$migration_future_tasks));
    $beamer_active = $beamer_due_checks || !$beamer_future_checks;

    return (
      $migration_active
      || $sending_queue_active
      || $bounce_sync_active
      || $sending_service_key_check_active
      || $premium_key_check_active
      || $stats_notifications_tasks
      || $auto_stats_notifications_tasks
      || $inactive_subscribers_tasks
      || $woo_commerce_sync_tasks
      || $authorized_email_addresses_tasks
      || $beamer_active
      || $woo_commerce_past_orders_tasks
      || $unsubscribe_tokens_tasks
      || $subscriber_link_tokens_tasks
    );
  }

  function stop() {
    $cron_daemon = $this->cron_helper->getDaemon();
    if ($cron_daemon) {
      $this->cron_helper->deactivateDaemon($cron_daemon);
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
    $this->tasks_counts = [];
    foreach ($rows as $r) {
      if (empty($this->tasks_counts[$r->type])) {
        $this->tasks_counts[$r->type] = [];
      }
      if (empty($this->tasks_counts[$r->type][$r->scheduled_in])) {
        $this->tasks_counts[$r->type][$r->scheduled_in] = [];
      }
      $this->tasks_counts[$r->type][$r->scheduled_in][$r->status ?: 'null'] = $r->count;
    }
  }

  private function getTasksCount(array $options) {
    $count = 0;
    $type = $options['type'];
    foreach ($options['scheduled_in'] as $scheduled_in) {
      foreach ($options['status'] as $status) {
        if (! empty($this->tasks_counts[$type][$scheduled_in][$status])) {
          $count += $this->tasks_counts[$type][$scheduled_in][$status];
        }
      }
    }
    return $count;
  }
}
