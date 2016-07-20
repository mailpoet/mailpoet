<?php
namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Config\TaskScheduler as TaskSchedulerConfig;
use MailPoet\Cron\CronHelper;

if(!defined('ABSPATH')) exit;

class TaskScheduler {
  static function complete() {
    // when there are no more queues to process and if the task
    // scheduler method is WP, delete the cron daemon
    $task_scheduler = TaskSchedulerConfig::getCurrentMethod();
    if($task_scheduler === TaskSchedulerConfig::METHOD_WORDPRESS) {
      CronHelper::deleteDaemon();
    }
  }
}