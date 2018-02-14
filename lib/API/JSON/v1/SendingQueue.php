<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue as SendingQueueModel;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Segments\SubscribersFinder;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class SendingQueue extends APIEndpoint {
  public $permissions = array(
    'global' => AccessControl::PERMISSION_MANAGE_EMAILS
  );

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
        APIError::NOT_FOUND => __('This newsletter does not exist.', 'mailpoet')
      ));
    }

    // check that the sending method has been configured properly
    try {
      new Mailer(false);
    } catch(\Exception $e) {
      return $this->errorResponse(array(
        $e->getCode() => $e->getMessage()
      ));
    }

    // add newsletter to the sending queue
    $queue = SendingQueueModel::joinWithTasks()
      ->where('queues.newsletter_id', $newsletter->id)
      ->whereNull('tasks.status')
      ->findOne();

    if(!empty($queue)) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This newsletter is already being sent.', 'mailpoet')
      ));
    }
    $task = SendingQueueModel::findTaskByNewsletterId($newsletter->id)
      ->where('tasks.status', SendingQueueModel::STATUS_SCHEDULED)
      ->findOne();
    if($task) {
      $queue = SendingTask::createFromTask($task);
    } else {
      $queue = SendingTask::create();
      $queue->newsletter_id = $newsletter->id;
    }

    if((bool)$newsletter->isScheduled) {
      // set newsletter status
      $newsletter->setStatus(Newsletter::STATUS_SCHEDULED);

      // set queue status
      $queue->status = SendingQueueModel::STATUS_SCHEDULED;
      $queue->scheduled_at = Scheduler::formatDatetimeString(
        $newsletter->scheduledAt
      );
      $queue->removeAllSubscribers();
    } else {
      $segments = $newsletter->segments()->findArray();
      $finder = new SubscribersFinder();
      $subscribers_count = $finder->addSubscribersToTaskFromSegments($queue->task(), $segments);
      if(!$subscribers_count) {
        return $this->errorResponse(array(
          APIError::UNKNOWN => __('There are no subscribers in that list!', 'mailpoet')
        ));
      }
      $queue->updateCount();
      $queue->status = null;
      $queue->scheduled_at = null;

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
        APIError::NOT_FOUND => __('This newsletter does not exist.', 'mailpoet')
      ));
    } else {
      $queue = $newsletter->getQueue();

      if($queue === false) {
        return $this->errorResponse(array(
          APIError::UNKNOWN => __('This newsletter has not been sent yet.', 'mailpoet')
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
        APIError::NOT_FOUND => __('This newsletter does not exist.', 'mailpoet')
      ));
    } else {
      $queue = $newsletter->getQueue();

      if($queue === false) {
        return $this->errorResponse(array(
          APIError::UNKNOWN => __('This newsletter has not been sent yet.', 'mailpoet')
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
