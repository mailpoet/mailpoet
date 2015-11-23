<?php
namespace MailPoet\Router;

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

  function pause() {
    wp_send_json(
      array(
        'result' => ($this->updateQueueStatus('paused') ?
          true :
          false
        )
      )
    );
  }

  function stop() {
    wp_send_json(
      array(
        'result' => ($this->updateQueueStatus('stopped') ?
          true :
          false
        )
      )
    );
  }

  private function updateQueueStatus($status) {
    $queue = new \MailPoet\Queue\Queue();
    if(!$queue->queue || $queue->queueData['status'] !== 'started') {
      return false;
    }
    $queue->queueData['status'] = $status;
    $queue->queue->value = serialize($queue->queueData);
    return $queue->queue->save();
  }
}
