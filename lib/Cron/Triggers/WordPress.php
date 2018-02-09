<?php
namespace MailPoet\Cron\Triggers;

use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\Scheduler as SchedulerWorker;
use MailPoet\Cron\Workers\SendingQueue\Migration as MigrationWorker;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Cron\Workers\Bounce as BounceWorker;
use MailPoet\Cron\Workers\KeyCheck\PremiumKeyCheck as PremiumKeyCheckWorker;
use MailPoet\Cron\Workers\KeyCheck\SendingServiceKeyCheck as SendingServiceKeyCheckWorker;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\Setting;
use MailPoet\Services\Bridge;

if(!defined('ABSPATH')) exit;

class WordPress {
  static function run() {
    return (self::checkExecutionRequirements()) ?
      MailPoet::run() :
      self::cleanup();
  }

  static function checkExecutionRequirements() {
    // migration
    $migration_disabled = Setting::getValue('cron_trigger.method') === 'none';
    $migration_due_tasks = MigrationWorker::getAllDueTasks();
    $migration_future_tasks = MigrationWorker::getFutureTasks();
    // sending queue
    $scheduled_queues = SchedulerWorker::getScheduledQueues();
    $running_queues = SendingQueueWorker::getRunningQueues();
    $sending_limit_reached = MailerLog::isSendingLimitReached();
    $sending_is_paused = MailerLog::isSendingPaused();
    // sending service
    $mp_sending_enabled = Bridge::isMPSendingServiceEnabled();
    // bounce sync
    $bounce_due_tasks = BounceWorker::getAllDueTasks();
    $bounce_future_tasks = BounceWorker::getFutureTasks();
    // sending service key check
    $msskeycheck_due_tasks = SendingServiceKeyCheckWorker::getAllDueTasks();
    $msskeycheck_future_tasks = SendingServiceKeyCheckWorker::getFutureTasks();
    // premium key check
    $premium_key_specified = Bridge::isPremiumKeySpecified();
    $premium_keycheck_due_tasks = PremiumKeyCheckWorker::getAllDueTasks();
    $premium_keycheck_future_tasks = PremiumKeyCheckWorker::getFutureTasks();
    // check requirements for each worker
    $sending_queue_active = (($scheduled_queues || $running_queues) && !$sending_limit_reached && !$sending_is_paused);
    $bounce_sync_active = ($mp_sending_enabled && ($bounce_due_tasks || !$bounce_future_tasks));
    $sending_service_key_check_active = ($mp_sending_enabled && ($msskeycheck_due_tasks || !$msskeycheck_future_tasks));
    $premium_key_check_active = ($premium_key_specified && ($premium_keycheck_due_tasks || !$premium_keycheck_future_tasks));
    $migration_active = !$migration_disabled && ($migration_due_tasks || !$migration_future_tasks);

    return (
      $migration_active
      || $sending_queue_active
      || $bounce_sync_active
      || $sending_service_key_check_active
      || $premium_key_check_active
    );
  }

  static function cleanup() {
    $cron_daemon = CronHelper::getDaemon();
    if($cron_daemon) {
      CronHelper::deleteDaemon();
    }
  }
}