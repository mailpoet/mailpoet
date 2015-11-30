<?php
namespace MailPoet\Router;

use MailPoet\Queue\Daemon;
use MailPoet\Queue\Supervisor;

if(!defined('ABSPATH')) exit;

class Queue {
  function controlDaemon($data) {
    switch($data['action']) {
      case 'start':
        $supervisor = new Supervisor($forceStart = true);
        wp_send_json(
          array(
            'result' => $supervisor->checkDaemon() ?
              true :
              false
          )
        );
        break;
      case 'stop':
        $status = 'stopped';
        break;
      default:
        $status = 'paused';
        break;
    }
    $daemon = new Daemon();
    if(!$daemon->daemon || $daemon->daemonData['status'] !== 'started') {
      $result = false;
    } else {
      $daemon->daemonData['status'] = $status;
      $daemon->daemon->value = json_encode($daemon->daemonData);
      $result = $daemon->daemon->save();
    }
    wp_send_json(
      array(
        'result' => $result
      )
    );
  }

  function getDaemonStatus() {
    $daemon = new \MailPoet\Queue\BootStrapMenu();
    wp_send_json($daemon->bootStrap());
  }
}