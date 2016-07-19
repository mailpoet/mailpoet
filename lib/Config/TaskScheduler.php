<?php
namespace MailPoet\Config;

use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Supervisor;
use MailPoet\Cron\Workers\Scheduler as SchedulerWorker;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class TaskScheduler {
  const METHOD_WORDPRESS = 'WordPress';
  const METHOD_MAILPOET = 'MailPoet';

  function __construct() {
    $this->method = self::getCurrentMethod();
  }

  function init() {
    // configure task scheduler only outside of cli environment
    if(php_sapi_name() === 'cli') return;
    switch($this->method) {
      case self::METHOD_MAILPOET:
        return $this->configureMailpoetScheduler();
      break;
      case self::METHOD_WORDPRESS:
        return $this->configureWordpressScheduler();
      break;
      default:
        throw new \Exception(__("Task scheduler is not configured"));
      break;
    };
  }

  function configureMailpoetScheduler() {
    try {
      $supervisor = new Supervisor();
      $supervisor->checkDaemon();
    } catch(\Exception $e) {
      // exceptions should not prevent the rest of the site loading
    }
  }

  function configureWordpressScheduler() {
    $scheduled_queues = SchedulerWorker::getScheduledQueues();
    $running_queues = SendingQueueWorker::getRunningQueues();
    // run cron only when there are scheduled queues ready to be processed
    // or are already being processed
    if($scheduled_queues || $running_queues) {
      return $this->configureMailpoetScheduler();
    }
    // stop (delete) daemon since the WP task scheduler is enabled
    $cron_daemon = CronHelper::getDaemon();
    if ($cron_daemon) {
      CronHelper::deleteDaemon();
    }
    return;
  }

  static function getAvailableMethods() {
    return array(
      'mailpoet' => self::METHOD_MAILPOET,
      'wordpress' => self::METHOD_WORDPRESS
    );
  }

  static function getCurrentMethod() {
    return Setting::getValue('task_scheduler.method');
  }
}