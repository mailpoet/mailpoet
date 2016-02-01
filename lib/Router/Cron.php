<?php
namespace MailPoet\Router;

use Carbon\Carbon;
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
    return (
      ($daemon) ?
        array_merge(
          array(
            'timeSinceStart' =>
              Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $daemon->created_at,
                'UTC'
              )->diffForHumans(),
            'timeSinceUpdate' =>
              Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $daemon->updated_at,
                'UTC'
              )->diffForHumans()
          ),
          unserialize($daemon->value)
        ) :
        "false"
    );
  }
}