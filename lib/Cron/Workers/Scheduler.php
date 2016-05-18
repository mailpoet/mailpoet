<?php
namespace MailPoet\Cron\Workers;

use Carbon\Carbon;
use MailPoet\Cron\CronHelper;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Util\Helpers;

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
      } else {
        if($newsletter->type === 'welcome') {
          $this->processWelcomeNewsletter($newsletter, $queue);
        } else {
          if($newsletter->type === 'notification') {
            $this->processPostNotificationNewsletter($newsletter, $queue);
          }
        }
      }
      CronHelper::checkExecutionTimer($this->timer);
    }
  }

  function processWelcomeNewsletter($newsletter, $queue) {
    $subscriber = unserialize($queue->subscribers);
    if(!isset($subscriber['to_process']) || !isset($subscriber['to_process'][0])) {
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
    $segments = unserialize($newsletter->segments);
    if(empty($segments)) {
      $queue->delete();
      return;
    }
    $subscribers = Subscriber::getSubscribedInSegments($segments)
      ->findArray();
    $subscribers = Helpers::arrayColumn($subscribers, 'subscriber_id');
    $subscribers = array_unique($subscribers);
    if(empty($subscribers)) {
      $queue->delete();
      return;
    }
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
    if(!$subscriber || $subscriber->wp_user_id === null) {
      $queue->delete();
      return false;
    }
    $wp_user = (array) get_userdata($subscriber->wp_user_id);
    if($newsletter->role !== \MailPoet\Newsletter\Scheduler\Scheduler::WORDPRESS_ALL_ROLES
      && !in_array($newsletter->role, $wp_user['roles'])
    ) {
      $queue->delete();
      return false;
    }
    return true;
  }
}