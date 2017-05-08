<?php
namespace MailPoet\Cron\Triggers;

use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\Scheduler as SchedulerWorker;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Cron\Workers\Bounce as BounceWorker;
use MailPoet\Cron\Workers\KeyCheck\PremiumKeyCheck as PremiumKeyCheckWorker;
use MailPoet\Cron\Workers\KeyCheck\SendingServiceKeyCheck as SendingServiceKeyCheckWorker;
use MailPoet\Mailer\MailerLog;
use MailPoet\Services\Bridge;

if(!defined('ABSPATH')) exit;

class WordPress {
  static function run() {
    return (self::checkExecutionRequirements()) ?
      MailPoet::run() :
      self::cleanup();
  }

  static function checkExecutionRequirements() {
    // sending queue
    $scheduled_queues = SchedulerWorker::getScheduledQueues();
    $running_queues = SendingQueueWorker::getRunningQueues();
    $sending_limit_reached = MailerLog::isSendingLimitReached();
    $sending_is_paused = MailerLog::isSendingPaused();
    // sending service
    $mp_sending_enabled = Bridge::isMPSendingServiceEnabled();
    // bounce sync
    $bounce_due_queues = BounceWorker::getAllDueQueues();
    $bounce_future_queues = BounceWorker::getFutureQueues();
    // sending service key check
    $sskeycheck_due_queues = SendingServiceKeyCheckWorker::getAllDueQueues();
    $sskeycheck_future_queues = SendingServiceKeyCheckWorker::getFutureQueues();
    // premium key check
    $premium_key_specified = Bridge::isPremiumKeySpecified();
    $premium_keycheck_due_queues = PremiumKeyCheckWorker::getAllDueQueues();
    $premium_keycheck_future_queues = PremiumKeyCheckWorker::getFutureQueues();
    // check requirements for each worker
    $sending_queue_active = (($scheduled_queues || $running_queues) && !$sending_limit_reached && !$sending_is_paused);
    $bounce_sync_active = ($mp_sending_enabled && ($bounce_due_queues || !$bounce_future_queues));
    $sending_service_key_check_active = ($mp_sending_enabled && ($sskeycheck_due_queues || !$sskeycheck_future_queues));
    $premium_key_check_active = ($premium_key_specified && ($premium_keycheck_due_queues || !$premium_keycheck_future_queues));

    return ($sending_queue_active || $bounce_sync_active || $sending_service_key_check_active || $premium_key_check_active);
  }

  static function cleanup() {
    $cron_daemon = CronHelper::getDaemon();
    if($cron_daemon) {
      CronHelper::deleteDaemon();
    }
  }
}