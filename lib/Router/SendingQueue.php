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
      return array(
        'result' => false,
        'errors' => array($e->getMessage())
      );
    }

    $queue = \MailPoet\Models\SendingQueue::whereNull('status')
      ->where('newsletter_id', $data['newsletter_id'])
      ->findArray();

    if(!empty($queue)) {
      return array(
        'result' => false,
        'errors' => array(__('Send operation is already in progress.'))
      );
    }
    $queue = \MailPoet\Models\SendingQueue::create();
    $queue->newsletter_id = $data['newsletter_id'];
    $subscriber_ids = array();
    $segments = Segment::whereIn('id', $data['segments'])
      ->findMany();

    foreach($segments as $segment) {
      $subscriber_ids = array_merge(
        $subscriber_ids,
        Helpers::arrayColumn(
          $segment->subscribers()->findArray(), 'id'
        )
      );
    }

    if(empty($subscriber_ids)) {
      return array(
        'result' => false,
        'errors' => array(__('There are no subscribers.'))
      );
    }

    $subscriber_ids = array_unique($subscriber_ids);
    $queue->subscribers = json_encode(
      array(
        'to_process' => $subscriber_ids
      )
    );
    $queue->count_total = $queue->count_to_process = count($subscriber_ids);
    $queue->save();
    $errors = $queue->getErrors();
    if(!empty($errors)) {
      return array(
        'result' => false,
        'errors' => $errors
      );
    } else {
      return array(
        'result' => true,
        'data' => array($queue->id)
      );
    }
  }

  function pause($newsletter_id) {
    $newsletter = Newsletter::findOne($newsletter_id);
    $result = false;

    if($newsletter !== false) {
      $queue = $newsletter->getQueue();

      if($queue !== false) {
        $result = $queue->pause();
      }
    }

    return array(
      'result' => $result
    );
  }

  function resume($newsletter_id) {
    $newsletter = Newsletter::findOne($newsletter_id);
    $result = false;

    if($newsletter !== false) {
      $queue = $newsletter->getQueue();

      if($queue !== false) {
        $result = $queue->resume();
      }
    }

    return array(
      'result' => $result
    );
  }
}