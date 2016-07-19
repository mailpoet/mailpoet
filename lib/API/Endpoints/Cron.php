<?php
namespace MailPoet\API\Endpoints;

use MailPoet\Config\TaskScheduler;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Supervisor;
use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Cron {
  function start() {
    $supervisor = new Supervisor($force_run = true);
    return $supervisor->checkDaemon();
  }

  function stop() {
    $daemon = CronHelper::getDaemon();
    if(!$daemon || $daemon['status'] !== 'started') {
      $result = false;
    } else {
      $daemon['status'] = 'stopping';
      $result = CronHelper::saveDaemon($daemon);
    }
    return array(
      'result' => $result
    );
  }

  function getStatus() {
    $task_scheduler = TaskScheduler::getCurrentMethod();
    $daemon = Setting::getSetting(CronHelper::DAEMON_SETTING);
    if($daemon) {
      return $daemon;
    }
    $status = ($task_scheduler === TaskScheduler::METHOD_WORDPRESS) ?
      'wordpress_task_scheduler_enabled' :
      false;
    return array('status' => $status);
  }
}