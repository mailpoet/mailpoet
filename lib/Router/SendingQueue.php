<?php
namespace MailPoet\Router;

use MailPoet\Models\Newsletter;
use MailPoet\Models\Segment;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class SendingQueue {
  function add($data) {
    // check if mailer is properly configured
    try {
      new Mailer(false);
    } catch(\Exception $e) {
      wp_send_json(
        array(
          'result' => false,
          'errors' => array($e->getMessage())
        )
      );
    }
    $queue = \MailPoet\Models\SendingQueue::where('newsletter_id', $data['newsletter_id'])
      ->whereNull('status')
      ->findArray();
    if(count($queue)) {
      wp_send_json(
        array(
          'result' => false,
          'errors' => array(__('Send operation is already in progress.'))
        )
      );
      exit;
    }
    $queue = \MailPoet\Models\SendingQueue::create();
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

    if(empty($subscriber_ids)) {
      wp_send_json(
        array(
          'result' => false,
          'errors' => array(__('There are no subscribers.'))
        )
      );
      exit;
    }

    $subscriber_ids = array_unique($subscriber_ids);
    $queue->subscribers = json_encode(
      array(
        'to_process' => $subscriber_ids
      )
    );
    $queue->count_total = $queue->count_to_process = count($subscriber_ids);
    $result = $queue->save();
    if($result === false) {
      $errors = array(__('Queue could not be created.'));

      if(!empty($queue->getValidationErrors())) {
        $errors = array_merge($errors, $queue->getValidationErrors());
      }

      wp_send_json(
        array(
          'result' => false,
          'errors' => $errors
        )
      );
    } else {
      wp_send_json(
        array(
          'result' => true,
          'data' => array($queue->id)
        )
      );
    }
  }

  function pause($newsletter_id) {
    $newsletter = Newsletter::findOne($newsletter_id);
    $result = false;

    if($newsletter !== false) {
      $queue = $newsletter->getQueue();
      if($queue !== false && $queue->id() > 0) {
        $result = $queue->pause();
      }
    }

    wp_send_json(array(
      'result' => $result
    ));
  }

  function resume($newsletter_id) {
    $newsletter = Newsletter::findOne($newsletter_id);
    $result = false;

    if($newsletter !== false) {
      $queue = $newsletter->getQueue();
      if($queue !== false && $queue->id() > 0) {
        $result = $queue->resume();
      }
    }

    wp_send_json(array(
      'result' => $result
    ));
  }

  function addQueues($data) {
    $newsletterIds = Helpers::arrayColumn($data, 'newsletter_id');
    $queues = \MailPoet\Models\SendingQueue::whereIn('newsletter_id', $newsletterIds)
      ->whereNull('status')
      ->findArray();
    if(count($queues)) {
      wp_send_json(
        array(
          'result' => false,
          'errors' => array(__('Send operation is already in progress.'))
        )
      );
    }
    $result = array_map(function ($queueData) {
      $queue = \MailPoet\Models\SendingQueue::create();
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
          'errors' => array(__('Some queues could not be created.')),
          'data' => $result
        ) :
        array(
          'result' => true,
          'data' => $result
        )
    );
  }

  function deleteQueue($data) {
    $queue = \MailPoet\Models\SendingQueue::whereNull('deleted_at')
      ->findOne($data['queue_id']);
    if(!$queue) {
      wp_send_json(
        array(
          'result' => false,
          'errors' => array(__('Queue not found.'))
        )
      );
    }
    $queue->deleted_at = 'Y-m-d H:i:s';
    $queue->save();
    wp_send_json(array('result' => true));
  }

  function deleteQueues($data) {
    $queues = \MailPoet\Models\SendingQueue::whereNull('deleted_at')
      ->whereIn('id', $data['queue_ids'])
      ->findResultSet();
    if(!$queues->count()) {
      wp_send_json(
        array(
          'result' => false,
          'errors' => array(__('Queues not found.'))
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
    $queue = \MailPoet\Models\SendingQueue::whereNull('deleted_at')
      ->findOne($data['queue_id'])
      ->asArray();
    wp_send_json(
      !$queue ?
        array(
          'result' => false,
          'errors' => array(__('Queue not found.'))
        ) :
        array(
          'result' => true,
          'data' => $queue
        )
    );
  }

  function getQueuesStatus($data) {
    $queues = \MailPoet\Models\SendingQueue::whereNull('deleted_at')
      ->whereIn('id', $data['queue_ids'])
      ->findArray();
    wp_send_json(
      !$queues ?
        array(
          'result' => false,
          'errors' => array(__('Queue not found.'))
        ) :
        array(
          'result' => true,
          'data' => $queues
        )
    );
  }
}