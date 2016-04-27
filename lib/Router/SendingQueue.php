<?php
namespace MailPoet\Router;

use MailPoet\Mailer\Mailer;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Scheduler\Scheduler;
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

    // create or update newsletter options, including schedule time
    $options = array();
    if(isset($data['options'])) {
      $options = $data['options'];
      unset($data['options']);
    }
    if ($options &&
      ($newsletter->type === 'notification' || $newsletter->type === 'welcome')
    ) {
      $option_fields = NewsletterOptionField::where(
        'newsletter_type', $newsletter->type
      )->findArray();
      foreach($option_fields as $option_field) {
        if(isset($options[$option_field['name']])) {
          $relation = NewsletterOption::where('option_field_id', $option_field['id'])
            ->where('newsletter_id', $newsletter->id)
            ->findOne();
          if (!$relation) {
            $relation = NewsletterOption::create();
            $relation->newsletter_id = $newsletter->id;
            $relation->option_field_id = $option_field['id'];
          }
          $relation->value = ($option_field['name'] === 'segments') ?
            serialize($data['segments']) :
            $options[$option_field['name']];
          $relation->save();
        }
      }
      if ($newsletter->type === 'notification') {
        Scheduler::postNotification($newsletter->id);
      }
      $newsletter = Newsletter::filter('filterWithOptions')
        ->findOne($data['newsletter_id']);
    }
    if($newsletter->type === 'welcome') {
      return array(
        'result' => true,
        'data' => array(
          'message' => __('Your welcome notification is activated.')
        )
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
      $queue = \MailPoet\Models\SendingQueue::where('status', 'scheduled')
        ->where('newsletter_id', $newsletter->id)
        ->findOne();
      if(!$queue) {
        $queue = \MailPoet\Models\SendingQueue::create();
        $queue->newsletter_id = $newsletter->id;
      }
      $schedule = Cron::factory($newsletter->schedule);
      $queue->scheduled_at =
        $schedule->getNextRunDate(current_time('mysql'))->format('Y-m-d H:i:s');
      $queue->status = 'scheduled';
      $queue->save();
      return array(
        'result' => true,
        'data' => array(
          'message' => __('Your post notifications is activated.')
        )
      );
    }

    $queue = \MailPoet\Models\SendingQueue::create();
    $queue->newsletter_id = $newsletter->id;

    $subscribers = Subscriber::getSubscribedInSegments($data['segments'])
      ->findArray();
    $subscribers = Helpers::arrayColumn($subscribers, 'subscriber_id');
    $subscribers = array_unique($subscribers);
    if(!count($subscribers)) {
      return array(
        'result' => false,
        'errors' => array(__('There are no subscribers.'))
      );
    }
    $queue->subscribers = serialize(
      array(
        'to_process' => $subscribers
      )
    );
    $queue->count_total = $queue->count_to_process = count($subscribers);
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
          'message' => __('The newsletter is being sent...')
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