<?php
namespace MailPoet\Router;

use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class Cron {
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
    $daemon = new \MailPoet\Cron\BootStrapMenu();
    wp_send_json($daemon->bootStrap());
  }

  function addQueue($data) {
    $queue = SendingQueue::where('newsletter_id', $data['newsletter_id'])
      ->whereNull('status')
      ->findArray();

    !d($queue);
    exit;
    $queue = SendingQueue::create();

    $queue->newsletter_id = $data['newsletter_id'];


    $subscriber_ids = array();
    $segments = Segment::whereIn('id', $data['segments'])
      ->findMany();
    foreach($segments as $segment) {
      $subscriber_ids = array_merge($subscriber_ids, Helpers::arrayColumn(
        $segment->subscribers()
          ->findArray(),
        'id'
      ));
    }

    $subscriber_ids = array_unique($subscriber_ids);
    $queue->subscribers = json_encode(
      array(
        'to_process' => $subscriber_ids
      )
    );

    $queue->count_total = $queue->count_to_process = count($subscriber_ids);
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
      $queue = SendingQueue::create();
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
    $queue = SendingQueue::whereNull('deleted_at')
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
    $queues = SendingQueue::whereNull('deleted_at')
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
    $queue = SendingQueue::whereNull('deleted_at')
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
    $queues = SendingQueue::whereNull('deleted_at')
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