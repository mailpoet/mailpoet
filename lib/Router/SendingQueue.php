<?php
namespace MailPoet\Router;

use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\Segment;
use MailPoet\Util\Helpers;
use Cron\CronExpression as Cron;

if(!defined('ABSPATH')) exit;

class SendingQueue {
  function add($data) {
    try {
      new Mailer(false);
    } catch(\Exception $e) {
      return array(
        'result' => false,
        'errors' => array($e->getMessage())
      );
    }

    $newsletter = Newsletter::filter('filterWithOptions')
      ->findOne($data['newsletter_id']);
    if(!$newsletter) {
      return array(
        'result' => false,
        'errors' => array(__('Newsletter does not exist.'))
      );
    }

    $queue = \MailPoet\Models\SendingQueue::whereNull('status')
      ->where('newsletter_id', $newsletter->id)
      ->findOne();
    if(!empty($queue)) {
      return array(
        'result' => false,
        'errors' => array(__('Send operation is already in progress.'))
      );
    }

    if($newsletter->type === 'notification') {
      $option_field = NewsletterOptionField::where('name', 'segments')
        ->where('newsletter_type', 'notification')
        ->findOne();
      $relation = NewsletterOption::where('option_field_id', $option_field->id)
        ->findOne();
      if(!$relation) {
        $relation = NewsletterOption::create();
        $relation->newsletter_id = $newsletter->id;
        $relation->option_field_id = $option_field->id;
      }
      $relation->value = serialize($data['segments']);
      $relation->save();

      $queue = \MailPoet\Models\SendingQueue::where('status', 'scheduled')
        ->where('newsletter_id', $newsletter->id)
        ->findOne();
      if(!$queue) {
        $queue = \MailPoet\Models\SendingQueue::create();
        $queue->newsletter_id = $newsletter->id;
      }
      $schedule = Cron::factory($newsletter->schedule);
      $queue->scheduled_at = $schedule->getNextRunDate()->format('Y-m-d H:i:s');
      $queue->status = 'scheduled';
      $queue->save();
      return array(
        'result' => true,
        'data' => array(__('Newsletter was scheduled for sending.'))
      );
    }

    $queue = \MailPoet\Models\SendingQueue::create();
    $queue->newsletter_id = $newsletter->id;
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
    $queue->subscribers = serialize(
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