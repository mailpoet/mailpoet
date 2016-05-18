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
    } else {
      $newsletter = $newsletter->asArray();
    }

    if($newsletter->type === 'welcome') {
      return array(
        'result' => true,
        'data' => array(
          'message' => __('Your welcome notification is activated.')
        )
      );
    } elseif ($newsletter->type === 'notification') {
      $newsletter = Scheduler::processPostNotificationSchedule($newsletter['id']);
      Scheduler::createPostNotificationQueue($newsletter);
    }

    $queue = \MailPoet\Models\SendingQueue::whereNull('status')
      ->where('newsletter_id', $newsletter['id'])
      ->findOne();
    if(!empty($queue)) {
      return array(
        'result' => false,
        'errors' => array(__('Send operation is already in progress.'))
      );
    }

    $queue = \MailPoet\Models\SendingQueue::where('status', 'scheduled')
      ->where('newsletter_id', $newsletter['id'])
      ->findOne();
    if(!$queue) {
      $queue = \MailPoet\Models\SendingQueue::create();
      $queue->newsletter_id = $newsletter['id'];
    }

    if($newsletter['type'] === 'notification') {
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

    if ((bool)$newsletter['isScheduled']) {
      $queue->status = 'scheduled';
      $queue->scheduled_at = Scheduler::scheduleFromTimestamp(
        $newsletter['scheduledAt']
      );

      $message = __('The newsletter has been scheduled.');
    } else {
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
