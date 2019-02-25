<?php
namespace MailPoet\Cron\Triggers;

use Carbon\Carbon;
use MailPoet\Services\Bridge;
use MailPoet\Cron\CronHelper;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\ScheduledTask;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\Cron\Workers\Bounce as BounceWorker;
use MailPoet\Cron\Workers\Scheduler as SchedulerWorker;
use MailPoet\Cron\Workers\SendingQueue\Migration as MigrationWorker;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Cron\Workers\KeyCheck\PremiumKeyCheck as PremiumKeyCheckWorker;
use MailPoet\Cron\Workers\StatsNotifications\Worker as StatsNotificationsWorker;
use MailPoet\Cron\Workers\KeyCheck\SendingServiceKeyCheck as SendingServiceKeyCheckWorker;

if (!defined('ABSPATH')) exit;

class WordPress {
  const SCHEDULED_IN_THE_PAST = 'past';
  const SCHEDULED_IN_THE_FUTURE = 'future';
  static private $tasks_counts;

  static function run() {
    return (self::checkExecutionRequirements()) ?
      MailPoet::run() :
      self::stop();
  }

  static function checkExecutionRequirements(WPFunctions $wp = null) {
    self::loadTasksCounts($wp ?: new WPFunctions);

    // migration
    $settings = new SettingsController();
    $migration_disabled = $settings->get('cron_trigger.method') === 'none';
    $migration_due_tasks = self::getTasksCount([
      'type' => MigrationWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED]
    ]);
    $migration_completed_tasks = self::getTasksCount([
      'type' => MigrationWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST, self::SCHEDULED_IN_THE_FUTURE],
      'status' => [ScheduledTask::STATUS_COMPLETED]
    ]);
    $migration_future_tasks = self::getTasksCount([
      'type' => MigrationWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_FUTURE],
      'status' => [ScheduledTask::STATUS_SCHEDULED]
    ]);
    // sending queue
    $scheduled_queues = SchedulerWorker::getScheduledQueues();
    $running_queues = SendingQueueWorker::getRunningQueues();
    $sending_limit_reached = MailerLog::isSendingLimitReached();
    $sending_is_paused = MailerLog::isSendingPaused();
    // sending service
    $mp_sending_enabled = Bridge::isMPSendingServiceEnabled();
    // bounce sync
    $bounce_due_tasks = self::getTasksCount([
      'type' => BounceWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED]
    ]);
    $bounce_future_tasks = self::getTasksCount([
      'type' => BounceWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_FUTURE],
      'status' => [ScheduledTask::STATUS_SCHEDULED]
    ]);
    // sending service key check
    $msskeycheck_due_tasks = self::getTasksCount([
      'type' => SendingServiceKeyCheckWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED]
    ]);
    $msskeycheck_future_tasks = self::getTasksCount([
      'type' => SendingServiceKeyCheckWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_FUTURE],
      'status' => [ScheduledTask::STATUS_SCHEDULED]
    ]);
    // premium key check
    $premium_key_specified = Bridge::isPremiumKeySpecified();
    $premium_keycheck_due_tasks = self::getTasksCount([
      'type' => PremiumKeyCheckWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED]
    ]);
    $premium_keycheck_future_tasks = self::getTasksCount([
      'type' => PremiumKeyCheckWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_FUTURE],
      'status' => [ScheduledTask::STATUS_SCHEDULED]
    ]);
    // stats notifications
    $stats_notifications_tasks = self::getTasksCount([
      'type' => StatsNotificationsWorker::TASK_TYPE,
      'scheduled_in' => [self::SCHEDULED_IN_THE_PAST],
      'status' => ['null', ScheduledTask::STATUS_SCHEDULED]
    ]);

    // check requirements for each worker
    $sending_queue_active = (($scheduled_queues || $running_queues) && !$sending_limit_reached && !$sending_is_paused);
    $bounce_sync_active = ($mp_sending_enabled && ($bounce_due_tasks || !$bounce_future_tasks));
    $sending_service_key_check_active = ($mp_sending_enabled && ($msskeycheck_due_tasks || !$msskeycheck_future_tasks));
    $premium_key_check_active = ($premium_key_specified && ($premium_keycheck_due_tasks || !$premium_keycheck_future_tasks));
    $migration_active = !$migration_disabled && ($migration_due_tasks || (!$migration_completed_tasks && !$migration_future_tasks));

    return (
      $migration_active
      || $sending_queue_active
      || $bounce_sync_active
      || $sending_service_key_check_active
      || $premium_key_check_active
      || $stats_notifications_tasks
    );
  }

  static function stop() {
    $cron_daemon = CronHelper::getDaemon();
    if ($cron_daemon) {
      CronHelper::deactivateDaemon($cron_daemon);
    }
  }

  static private function loadTasksCounts(WPFunctions $wp) {
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
      date('Y-m-d H:i:s', $wp->currentTime('timestamp')),
      self::SCHEDULED_IN_THE_PAST,
      self::SCHEDULED_IN_THE_FUTURE,
      ScheduledTask::$_table
    );
    $rows = ScheduledTask::rawQuery($query)->findMany();
    self::$tasks_counts = [];
    foreach ($rows as $r) {
      if (empty(self::$tasks_counts[$r->type])) {
        self::$tasks_counts[$r->type] = [];
      }
      if (empty(self::$tasks_counts[$r->type][$r->scheduled_in])) {
        self::$tasks_counts[$r->type][$r->scheduled_in] = [];
      }
      self::$tasks_counts[$r->type][$r->scheduled_in][$r->status ?: 'null'] = $r->count;
    }
  }

  static private function getTasksCount(array $options) {
    $count = 0;
    $type = $options['type'];
    foreach ($options['scheduled_in'] as $scheduled_in) {
      foreach ($options['status'] as $status) {
        if (! empty(self::$tasks_counts[$type][$scheduled_in][$status])) {
          $count += self::$tasks_counts[$type][$scheduled_in][$status];
        }
      }
    }
    return $count;
  }
}
