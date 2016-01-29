<?php
namespace MailPoet\Router;

use Carbon\Carbon;
use MailPoet\Cron\Supervisor;
use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Cron {
  function start() {
    $supervisor = new Supervisor($force_run = true);
    wp_send_json($supervisor->checkDaemon());
  }

  function stop() {
    $daemon = Supervisor::getDaemon();
    if(!$daemon || $daemon['status'] !== 'started') {
      $result = false;
    } else {
      $daemon['status'] = 'stopping';
      Supervisor::saveDaemon($daemon);
    }
    wp_send_json(
      array(
        'result' => $result
      )
    );
  }

  function getStatus() {
    $daemon = Setting::where('name', 'cron_daemon')
      ->findOne();
    wp_send_json(
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