<?php
namespace MailPoet\API
    ;

use MailPoet\Mailer\Mailer;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Models\SendingQueue as SendingQueueModel;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class SendingQueue {
  function add($data) {
    // check that the sending method has been configured properly
    try {
      new Mailer(false);
    } catch(\Exception $e) {
      return array(
        'result' => false,
        'errors' => array($e->getMessage())
      );
    }

    // check that the newsletter exists
    $newsletter = Newsletter::filter('filterWithOptions')
      ->findOne($data['newsletter_id']);

    if($newsletter === false) {
      return array(
        'result' => false,
        'errors' => array(__('This newsletter does not exist'))
      );
    }

    if($newsletter->type === Newsletter::TYPE_WELCOME ||
       $newsletter->type === Newsletter::TYPE_NOTIFICATION
    ) {
      // set newsletter status to active
      $result = $newsletter->setStatus(Newsletter::STATUS_ACTIVE);
      $errors = $result->getErrors();

      if(!empty($errors)) {
        return array(
          'result' => false,
          'errors' => $errors
        );
      } else {
        $message = ($newsletter->type === Newsletter::TYPE_WELCOME) ?
          __('Your Welcome Email has been activated') :
          __('Your Post Notification has been activated');
        return array(
          'result' => true,
          'data' => array(
            'message' => $message
          )
        );
      }
    }

    $queue = SendingQueueModel::whereNull('status')
      ->where('newsletter_id', $newsletter->id)
      ->findOne();

    if(!empty($queue)) {
      return array(
        'result' => false,
        'errors' => array(__('This newsletter is already being sent'))
      );
    }

    $queue = SendingQueueModel::where('newsletter_id', $newsletter->id)
      ->where('status', SendingQueueModel::STATUS_SCHEDULED)
      ->findOne();
    if(!$queue) {
      $queue = SendingQueueModel::create();
      $queue->newsletter_id = $newsletter->id;
    }

    if((bool)$newsletter->isScheduled) {
      // set newsletter status
      $newsletter->setStatus(Newsletter::STATUS_SCHEDULED);

      // set queue status
      $queue->status = SendingQueueModel::STATUS_SCHEDULED;
      $queue->scheduled_at = Scheduler::scheduleFromTimestamp(
        $newsletter->scheduledAt
      );
      $queue->subscribers = null;
      $queue->count_total = $queue->count_to_process = 0;

      $message = __('The newsletter has been scheduled');
    } else {
      $segments = $newsletter->segments()->findArray();
      $segment_ids = array_map(function($segment) {
        return $segment['id'];
      }, $segments);
      $subscribers = Subscriber::getSubscribedInSegments($segment_ids)
        ->findArray();
      $subscribers = Helpers::arrayColumn($subscribers, 'subscriber_id');
      $subscribers = array_unique($subscribers);
      if(!count($subscribers)) {
        return array(
          'result' => false,
          'errors' => array(__('There are no subscribers in that list!'))
        );
      }
      $queue->status = null;
      $queue->scheduled_at = null;
      $queue->subscribers = serialize(
        array(
          'to_process' => $subscribers
        )
      );
      $queue->count_total = $queue->count_to_process = count($subscribers);

      // set newsletter status
      $newsletter->setStatus(Newsletter::STATUS_SENDING);

      $message = __('The newsletter is being sent...');
    }
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
        'data' => array(
          'message' => $message
        )
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
