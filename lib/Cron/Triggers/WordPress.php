<?php
namespace MailPoet\Cron\Triggers;

use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\Scheduler as SchedulerWorker;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Cron\Workers\Bounce as BounceWorker;
use MailPoet\Cron\Workers\SendingServiceKeyCheck as SSKeyCheckWorker;
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
    // sending service
    $mp_sending_enabled = Bridge::isMPSendingServiceEnabled();
    // bounce sync
    $bounce_due_queues = BounceWorker::getAllDueQueues();
    $bounce_future_queues = BounceWorker::getFutureQueues();
    // sending service key check
    $sskeycheck_due_queues = SSKeyCheckWorker::getAllDueQueues();
    $sskeycheck_future_queues = SSKeyCheckWorker::getFutureQueues();
    return (($scheduled_queues || $running_queues) && !$sending_limit_reached)
      || ($mp_sending_enabled && ($bounce_due_queues || !$bounce_future_queues))
      || ($mp_sending_enabled && ($sskeycheck_due_queues || !$sskeycheck_future_queues));
  }

  static function cleanup() {
    $cron_daemon = CronHelper::getDaemon();
    if($cron_daemon) {
      CronHelper::deleteDaemon();
    }
  }
}