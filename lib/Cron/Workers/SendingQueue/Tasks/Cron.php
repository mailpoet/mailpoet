<?php
namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Cron\CronHelper;
use MailPoet\Cron\CronTrigger;

if(!defined('ABSPATH')) exit;

class Cron {
  static function complete() {
    // when there are no more queues to process and if the task
    // scheduler method is WP, delete the cron daemon
    $task_scheduler = CronTrigger::getCurrentMethod();
    if($task_scheduler === CronTrigger::METHOD_WORDPRESS) {
      CronHelper::deleteDaemon();
    }
  }
}