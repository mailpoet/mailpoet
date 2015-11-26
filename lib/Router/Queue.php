<?php
namespace MailPoet\Router;

use MailPoet\Models\Setting;
use MailPoet\Queue\Daemon;
use MailPoet\Queue\Supervisor;

if(!defined('ABSPATH')) exit;

class Queue {
  function start() {
    $supervisor = new Supervisor();
    wp_send_json(
      array(
        'result' => ($supervisor->checkDaemon($forceStart = true)) ?
          true :
          false
      )
    );
  }

  function update($data) {
    switch ($data['action']) {
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

  function getQueueStatus() {
    $daemon = new \MailPoet\Queue\BootStrapMenu();
    wp_send_json($daemon->bootStrap());

  }
}