<?php
namespace MailPoet\Cron\Workers;

use Carbon\Carbon;
use MailPoet\Cron\CronHelper;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Util\Helpers;
use MailPoet\Newsletter\Scheduler\Scheduler as NewsletterScheduler;

require_once(ABSPATH . 'wp-includes/pluggable.php');

if(!defined('ABSPATH')) exit;

class Scheduler {
  public $timer;
  const UNCONFIRMED_SUBSCRIBER_RESCHEDULE_TIMEOUT = 5;

  function __construct($timer = false) {
    $this->timer = ($timer) ? $timer : microtime(true);
    CronHelper::checkExecutionTimer($this->timer);
  }

  function process() {
    $scheduled_queues = SendingQueue::where('status', 'scheduled')
      ->whereLte('scheduled_at', Carbon::createFromTimestamp(current_time('timestamp')))
      ->findMany();
    if(!count($scheduled_queues)) return;
    foreach($scheduled_queues as $i => $queue) {
      $newsletter = Newsletter::filter('filterWithOptions')
        ->findOne($queue->newsletter_id);
      if(!$newsletter || $newsletter->deleted_at !== null) {
        $queue->delete();
      } elseif($newsletter->type === 'welcome') {
        $this->processWelcomeNewsletter($newsletter, $queue);
      } elseif($newsletter->type === 'notification') {
        $this->processPostNotificationNewsletter($newsletter, $queue);
      } elseif($newsletter->type === 'standard') {
        $this->processScheduledStandardNewsletter($newsletter, $queue);
      }
      CronHelper::checkExecutionTimer($this->timer);
    }
  }

  function processWelcomeNewsletter($newsletter, $queue) {
    $subscriber = unserialize($queue->subscribers);
    if(empty($subscriber['to_process'][0])) {
      $queue->delete();
      return;
    }
    $subscriber_id = (int)$subscriber['to_process'][0];
    if($newsletter->event === 'segment') {
      if($this->verifyMailPoetSubscriber($subscriber_id, $newsletter, $queue) === false) {
        return;
      }
    } else {
      if($newsletter->event === 'user') {
        if($this->verifyWPSubscriber($subscriber_id, $newsletter, $queue) === false) {
          return;
        }
      }
    }
    $queue->status = null;
    $queue->save();
  }

  function processPostNotificationNewsletter($newsletter, $queue) {
    // ensure that segments exist
    $segments = $newsletter->segments()->findArray();
    if(empty($segments)) {
      $this->deleteQueueOrUpdateNextRunDate($queue, $newsletter);
      return;
    }
    $segment_ids = array_map(function($segment) {
      return $segment['id'];
    }, $segments);

    // ensure that subscribers are in segments
    $subscribers = Subscriber::getSubscribedInSegments($segment_ids)
      ->findArray();
    $subscribers = Helpers::arrayColumn($subscribers, 'subscriber_id');
    $subscribers = array_unique($subscribers);
    if(empty($subscribers)) {
      $this->deleteQueueOrUpdateNextRunDate($queue, $newsletter);
      return;
    }

    // create a duplicate newsletter that acts as a history record
    $notification_history = $this->createNotificationHistory($newsletter->id);
    if(!$notification_history) return;

    // queue newsletter for delivery
    $queue->newsletter_id = $notification_history->id;
    $queue->subscribers = serialize(
      array(
        'to_process' => $subscribers
      )
    );
    $queue->count_total = $queue->count_to_process = count($subscribers);
    $queue->status = null;
    $queue->save();
  }

  function processScheduledStandardNewsletter($newsletter, $queue) {
    $segments = $newsletter->segments()->findArray();
    $segment_ids = array_map(function($segment) {
      return $segment['id'];
    }, $segments);

    $subscribers = Subscriber::getSubscribedInSegments($segment_ids)
      ->findArray();
    $subscribers = Helpers::arrayColumn($subscribers, 'subscriber_id');
    $subscribers = array_unique($subscribers);

    // update current queue
    $queue->subscribers = serialize(
      array(
        'to_process' => $subscribers
      )
    );
    $queue->count_total = $queue->count_to_process = count($subscribers);
    $queue->status = null;
    $queue->save();
  }

  function verifyMailPoetSubscriber($subscriber_id, $newsletter, $queue) {
    $subscriber = Subscriber::findOne($subscriber_id);
    // check if subscriber is in proper segment
    $subscriber_in_segment =
      SubscriberSegment::where('subscriber_id', $subscriber_id)
        ->where('segment_id', $newsletter->segment)
        ->where('status', 'subscribed')
        ->findOne();
    if(!$subscriber || !$subscriber_in_segment) {
      $queue->delete();
      return false;
    }
    // check if subscriber is confirmed (subscribed)
    if($subscriber->status !== 'subscribed') {
      // reschedule delivery in 5 minutes
      $scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
      $queue->scheduled_at = $scheduled_at->addMinutes(
        self::UNCONFIRMED_SUBSCRIBER_RESCHEDULE_TIMEOUT
      );
      $queue->save();
      return false;
    }
    return true;
  }

  function verifyWPSubscriber($subscriber_id, $newsletter, $queue) {
    // check if user has the proper role
    $subscriber = Subscriber::findOne($subscriber_id);
    if(!$subscriber || $subscriber->isWPUser() === false) {
      $queue->delete();
      return false;
    }
    $wp_user = (array)get_userdata($subscriber->wp_user_id);
    if($newsletter->role !== \MailPoet\Newsletter\Scheduler\Scheduler::WORDPRESS_ALL_ROLES
      && !in_array($newsletter->role, $wp_user['roles'])
    ) {
      $queue->delete();
      return false;
    }
    return true;
  }

  function deleteQueueOrUpdateNextRunDate($queue, $newsletter) {
    if($newsletter->intervalType === NewsletterScheduler::INTERVAL_IMMEDIATELY) {
      $queue->delete();
    } else {
      $next_run_date = NewsletterScheduler::getNextRunDate($newsletter->schedule);
      $queue->scheduled_at = $next_run_date;
      $queue->save();
    }
  }

  function createNotificationHistory($newsletter_id) {
    $newsletter = Newsletter::findOne($newsletter_id);
    $notification_history = $newsletter->createNotificationHistory();
    return ($notification_history->getErrors() === false) ?
      $notification_history :
      false;
  }
}