<?php
namespace MailPoet\Router;

use MailPoet\Queue\Daemon;
use MailPoet\Queue\Supervisor;
use MailPoet\Util\Helpers;

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

  function addQueue($data) {
    $queue = \MailPoet\Models\Queue::create();
    $queue->newsletter_id = $data['newsletter_id'];
    $queue->subscribers = json_encode(
      array(
        'to_process' => $data['subscribers']
      )
    );
    $queue->count_total = $queue->count_to_process = count($data['subscribers']);
    $queue->save();
    wp_send_json(
      !$queue->save() ?
        array(
          'result' => false,
          'error' => 'Queue could not be created.'
        ) :
        array(
          'result' => true,
          'data' => array($queue->id)
        )
    );
  }

  function addQueues($data) {
    $result = array_map(function ($queueData) {
      $queue = \MailPoet\Models\Queue::create();
      $queue->newsletter_id = $queueData['newsletter_id'];
      $queue->subscribers = json_encode(
        array(
          'to_process' => $queueData['subscribers']
        )
      );
      $queue->count_total = $queue->count_to_process = count($queueData['subscribers']);
      $queue->save();
      return array(
        'newsletter_id' => $queue->newsletter_id,
        'queue_id' => $queue->id
      );
    }, $data);
    $result = Helpers::arrayColumn($result, 'queue_id', 'newsletter_id');
    wp_send_json(
      count($data) != count($result) ?
        array(
          'result' => false,
          'error' => __('Some queues could not be created.'),
          'data' => $result
        ) :
        array(
          'result' => true,
          'data' => $result
        )
    );
  }

  function deleteQueue($data) {
    $queue = \MailPoet\Models\Queue::whereNull('deleted_at')
      ->findOne($data['queue_id']);
    if(!$queue) {
      wp_send_json(
        array(
          'result' => false,
          'error' => __('Queue not found.')
        )
      );
    }
    $queue->deleted_at = 'Y-m-d H:i:s';
    $queue->save();
    wp_send_json(array('result' => true));
  }

  function deleteQueues($data) {
    $queues = \MailPoet\Models\Queue::whereNull('deleted_at')
      ->whereIn('id', $data['queue_ids'])
      ->findResultSet();
    if(!$queues->count()) {
      wp_send_json(
        array(
          'result' => false,
          'error' => __('Queues not found.')
        )
      );
    }
    foreach($queues as $queue) {
      $queue->deleted_at = 'Y-m-d H:i:s';
      $queue->save();
    }
    wp_send_json(array('result' => true));
  }

  function getQueueStatus($data) {
    $queue = \MailPoet\Models\Queue::whereNull('deleted_at')
      ->findOne($data['queue_id'])
      ->asArray();
    wp_send_json(
      !$queue ?
        array(
          'result' => false,
          'error' => __('Queue not found.')
        ) :
        array(
          'result' => true,
          'data' => $queue
        )
    );
  }

  function getQueuesStatus($data) {
    $queues = \MailPoet\Models\Queue::whereNull('deleted_at')
      ->whereIn('id', $data['queue_ids'])
      ->findArray();
    wp_send_json(
      !$queues ?
        array(
          'result' => false,
          'error' => __('Queue not found.')
        ) :
        array(
          'result' => true,
          'data' => $queues
        )
    );
  }
}