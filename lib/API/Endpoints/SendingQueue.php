<?php
namespace MailPoet\API\Endpoints;
use MailPoet\API\Endpoint as APIEndpoint;
use MailPoet\API\Error as APIError;

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

class SendingQueue extends APIEndpoint {
  function add($data = array()) {
    $newsletter_id = (isset($data['newsletter_id'])
      ? (int)$data['newsletter_id']
      : false
    );

    // check that the newsletter exists
    $newsletter = Newsletter::filter('filterWithOptions')
      ->findOne($newsletter_id);

    if($newsletter === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This newsletter does not exist.')
      ));
    }

<<<<<<< HEAD:lib/Router/SendingQueue.php
    if($newsletter->type === Newsletter::TYPE_WELCOME) {
      // set welcome email active
      $result = $newsletter->setStatus(Newsletter::STATUS_ACTIVE);
      $errors = $result->getErrors();

      if(!empty($errors)) {
        return array(
          'result' => false,
          'errors' => $errors
        );
      } else {
        return array(
          'result' => true,
          'data' => array(
            'message' => __('Your Welcome Email has been activated')
          )
        );
      }
    } else if($newsletter->type === Newsletter::TYPE_NOTIFICATION) {
      // Post Notifications
      $newsletter = Scheduler::processPostNotificationSchedule($newsletter->id);
      Scheduler::createPostNotificationQueue($newsletter);

      // set post notification active
      $newsletter->setStatus(Newsletter::STATUS_ACTIVE);
=======
    // check that the sending method has been configured properly
    try {
      new Mailer(false);
    } catch(\Exception $e) {
      return $this->errorResponse(array(
        $e->getCode() => $e->getMessage()
      ));
>>>>>>> master:lib/API/Endpoints/SendingQueue.php
    }

    // add newsletter to the sending queue
    $queue = SendingQueueModel::whereNull('status')
    ->where('newsletter_id', $newsletter->id)
    ->findOne();

    if(!empty($queue)) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This newsletter is already being sent.')
      ));
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
<<<<<<< HEAD:lib/Router/SendingQueue.php
        return array(
          'result' => false,
          'errors' => array(__('There are no subscribers in that list!'))
        );
=======
        return $this->errorResponse(array(
          APIError::UNKNOWN => __('There are no subscribers in that list!')
        ));
>>>>>>> master:lib/API/Endpoints/SendingQueue.php
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
    }
    $queue->save();

    $errors = $queue->getErrors();
    if(!empty($errors)) {
      return $this->errorResponse($errors);
    } else {
      return $this->successResponse(
        $newsletter->getQueue()->asArray()
      );
    }
  }

  function pause($data = array()) {
    $newsletter_id = (isset($data['newsletter_id'])
      ? (int)$data['newsletter_id']
      : false
    );
    $newsletter = Newsletter::findOne($newsletter_id);

    if($newsletter === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This newsletter does not exist.')
      ));
    } else {
      $queue = $newsletter->getQueue();

      if($queue === false) {
        return $this->errorResponse(array(
          APIError::UNKNOWN => __('This newsletter has not been sent yet.')
        ));
      } else {
        $queue->pause();
        return $this->successResponse(
          $newsletter->getQueue()->asArray()
        );
      }
    }
  }

  function resume($data = array()) {
    $newsletter_id = (isset($data['newsletter_id'])
      ? (int)$data['newsletter_id']
      : false
    );
    $newsletter = Newsletter::findOne($newsletter_id);
    if($newsletter === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This newsletter does not exist.')
      ));
    } else {
      $queue = $newsletter->getQueue();

      if($queue === false) {
        return $this->errorResponse(array(
          APIError::UNKNOWN => __('This newsletter has not been sent yet.')
        ));
      } else {
        $queue->resume();
        return $this->successResponse(
          $newsletter->getQueue()->asArray()
        );
      }
    }
  }
}
