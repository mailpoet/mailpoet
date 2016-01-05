<?php
namespace MailPoet\Router;

use MailPoet\Cron\Daemon;
use MailPoet\Cron\Supervisor;

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
      $daemon->daemonData['status'] !== 'started'
    ) {
      $result = false;
    } else {
      $daemon->daemonData['status'] = 'stopping';
      $daemon->daemon->value = json_encode($daemon->daemonData);
      $result = $daemon->daemon->save();
    }
    wp_send_json(
      array(
        'result' => $result
      )
    );
  }

  function getStatus() {
    $daemon = new \MailPoet\Cron\BootStrapMenu();
    wp_send_json($daemon->bootStrap());
  }
}