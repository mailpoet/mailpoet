<?php

namespace MailPoet\Cron\Workers;

use Carbon\Carbon;
use MailPoet\Cron\CronHelper;
use MailPoet\Models\Newsletter;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Segments\SubscribersFinder;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Newsletter\Scheduler\Scheduler as NewsletterScheduler;
use MailPoet\WP\Functions as WPFunctions;

if(!defined('ABSPATH')) exit;
require_once(ABSPATH . 'wp-includes/pluggable.php');

class Scheduler {
  public $timer;
  const UNCONFIRMED_SUBSCRIBER_RESCHEDULE_TIMEOUT = 5;

  function __construct($timer = false) {
    $this->timer = ($timer) ? $timer : microtime(true);
    // abort if execution limit is reached
    CronHelper::enforceExecutionLimit($this->timer);
  }

  function process() {
    $scheduled_queues = self::getScheduledQueues();
    if(!count($scheduled_queues)) return false;
    foreach($scheduled_queues as $i => $queue) {
      $newsletter = Newsletter::filter('filterWithOptions')
        ->findOne($queue->newsletter_id);
      if(!$newsletter || $newsletter->deleted_at !== null) {
        $queue->delete();
      } elseif($newsletter->status !== Newsletter::STATUS_ACTIVE && $newsletter->status !== Newsletter::STATUS_SCHEDULED) {
        continue;
      } elseif($newsletter->type === Newsletter::TYPE_WELCOME) {
        $this->processWelcomeNewsletter($newsletter, $queue);
      } elseif($newsletter->type === Newsletter::TYPE_NOTIFICATION) {
        $this->processPostNotificationNewsletter($newsletter, $queue);
      } elseif($newsletter->type === Newsletter::TYPE_STANDARD) {
        $this->processScheduledStandardNewsletter($newsletter, $queue);
      }
      CronHelper::enforceExecutionLimit($this->timer);
    }
  }

  function processWelcomeNewsletter($newsletter, $queue) {
    $subscribers = $queue->getSubscribers();
    if(empty($subscribers[0])) {
      $queue->delete();
      return false;
    }
    $subscriber_id = (int)$subscribers[0];
    if($newsletter->event === 'segment') {
      if($this->verifyMailpoetSubscriber($subscriber_id, $newsletter, $queue) === false) {
        return false;
      }
    } else {
      if($newsletter->event === 'user') {
        if($this->verifyWPSubscriber($subscriber_id, $newsletter, $queue) === false) {
          return false;
        }
      }
    }
    $queue->status = null;
    $queue->save();
    return true;
  }

  function processPostNotificationNewsletter($newsletter, $queue) {
    // ensure that segments exist
    $segments = $newsletter->segments()->findArray();
    if(empty($segments)) {
      return $this->deleteQueueOrUpdateNextRunDate($queue, $newsletter);
    }

    // ensure that subscribers are in segments

    $finder = new SubscribersFinder();
    $subscribers_count = $finder->addSubscribersToTaskFromSegments($queue->task(), $segments);

    if(empty($subscribers_count)) {
      return $this->deleteQueueOrUpdateNextRunDate($queue, $newsletter);
    }

    // create a duplicate newsletter that acts as a history record
    $notification_history = $this->createNotificationHistory($newsletter->id);
    if(!$notification_history) return false;

    // queue newsletter for delivery
    $queue->newsletter_id = $notification_history->id;
    $queue->status = null;
    $queue->save();
    // update notification status
    $notification_history->setStatus(Newsletter::STATUS_SENDING);
    return true;
  }

  function processScheduledStandardNewsletter($newsletter, $queue) {
    $segments = $newsletter->segments()->findArray();
    $finder = new SubscribersFinder();
    $subscribers_count = $finder->addSubscribersToTaskFromSegments($queue->task(), $segments);
    // update current queue
    $queue->updateCount();
    $queue->status = null;
    $queue->save();
    // update newsletter status
    $newsletter->setStatus(Newsletter::STATUS_SENDING);
    return true;
  }

  function verifyMailpoetSubscriber($subscriber_id, $newsletter, $queue) {
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
    if($subscriber->status !== Subscriber::STATUS_SUBSCRIBED) {
      // reschedule delivery in 5 minutes
      $scheduled_at = Carbon::createFromTimestamp(WPFunctions::currentTime('timestamp'));
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
      return;
    } else {
      $next_run_date = NewsletterScheduler::getNextRunDate($newsletter->schedule);
      if(!$next_run_date) {
        $queue->delete();
        return;
      }
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

  static function getScheduledQueues() {
    return SendingTask::getScheduledQueues();
  }
}