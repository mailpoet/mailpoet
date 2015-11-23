<?php
namespace MailPoet\Router;

use MailPoet\Queue\Daemon;
use MailPoet\Queue\Supervisor;

if(!defined('ABSPATH')) exit;

class Queue {
  function start() {
    $supervisor = new Supervisor();
    wp_send_json(
      array(
        'result' => ($supervisor->checkQueue($forceStart = true)) ?
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
    $queue = new Daemon();
    if(!$queue->queue || $queue->queueData['status'] !== 'started') {
      $result = false;
    } else {
      $queue->queueData['status'] = $status;
      $queue->queue->value = serialize($queue->queueData);
      $result = $queue->queue->save();
    }
    wp_send_json(
      array(
        'result' => $result
      )
    );
  }
}