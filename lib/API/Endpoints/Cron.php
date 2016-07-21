<?php
namespace MailPoet\API\Endpoints;

use MailPoet\Cron\CronHelper;
use MailPoet\Cron\CronTrigger;
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
    $task_scheduler = CronTrigger::getCurrentMethod();
    $daemon = Setting::getValue(CronHelper::DAEMON_SETTING);
    if($daemon) {
      return $daemon;
    }
    $status = ($task_scheduler === CronTrigger::METHOD_WORDPRESS) ?
      'wordpress_task_scheduler_enabled' :
      false;
    return array('status' => $status);
  }
}