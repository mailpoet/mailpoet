<?php
namespace MailPoet\API;


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
    $daemon = Setting::where('name', 'cron_daemon')
      ->findOne();
    return ($daemon) ?
      unserialize($daemon->value) :
      false;
  }
}