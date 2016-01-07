<?php
namespace MailPoet\Router;

use Carbon\Carbon;
use MailPoet\Cron\Daemon;
use MailPoet\Cron\Supervisor;
use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Cron {
  function start() {
    $supervisor = new Supervisor($forceStart = true);
    wp_send_json(
      array(
        'result' => $supervisor->checkDaemon() ? true : false
      )
    );
  }

  function stop() {
    $daemon = new Daemon();
    if(!$daemon->daemon ||
      $daemon->daemon['status'] !== 'started'
    ) {
      $result = false;
    } else {
      $daemon->daemon['status'] = 'stopping';
      $result = $daemon->saveDaemon($daemon->daemon);
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