<?php
namespace MailPoet\Cron\Workers;

use Carbon\Carbon;
use Cron\CronExpression as Cron;
use MailPoet\Cron\CronHelper;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Util\Helpers;

require_once(ABSPATH . 'wp-includes/pluggable.php');

if(!defined('ABSPATH')) exit;

class Scheduler {
  public $timer;

  function __construct($timer = false) {
    $this->timer = ($timer) ? $timer : microtime(true);
    CronHelper::checkExecutionTimer($this->timer);
  }

  function process() {
    $scheduled_queues = SendingQueue::where('status', 'scheduled')
      ->whereLte('scheduled_at', Carbon::createFromTimestamp(current_time('timestamp')))
      ->findMany();
    if(!count($scheduled_queues)) return;
    foreach($scheduled_queues as $queue) {
      $newsletter = Newsletter::filter('filterWithOptions')
        ->findOne($queue->newsletter_id);
      if(!$newsletter || $newsletter->deleted_at !== null) {
        $queue->delete();
      }
      else if($newsletter->type === 'welcome') {
        $this->processWelcomeNewsletter($newsletter, $queue);
      }
      else if($newsletter->type === 'notification') {
        $this->processPostNotificationNewsletter($newsletter, $queue);
      }
      CronHelper::checkExecutionTimer($this->timer);
    }
  }

  function processWelcomeNewsletter($newsletter, $queue) {
    $subscriber = unserialize($queue->subscribers);
    $subscriber_id = $subscriber['to_process'][0];
    $result = false;
    if($newsletter->event === 'segment') {
      $result = $this->verifyMailPoetSubscriber($subscriber_id, $newsletter);
    }
    else if($newsletter->event === 'user') {
      $result = $this->verifyWPSubscriber($subscriber_id, $newsletter);
    }
    if(!$result) {
      $queue->delete();
      return;
    }
    $queue->status = null;
    $queue->save();
  }

  function processPostNotificationNewsletter($newsletter, $queue) {
    $next_run_date = $this->getQueueNextRunDate($newsletter->schedule);
    $segments = unserialize($newsletter->segments);
    $subscribers = SubscriberSegment::whereIn('segment_id', $segments)
      ->findArray();
    $subscribers = Helpers::arrayColumn($subscribers, 'subscriber_id');
    $subscribers = array_unique($subscribers);
    if(!count($subscribers)) {
      $queue->delete();
      return;
    }
    if(!$this->checkIfNewsletterChanged($newsletter)) {
      $queue->scheduled_at = $next_run_date;
      $queue->save();
      return;
    }
    // update current queue
    $queue->subscribers = serialize(
      array(
        'to_process' => $subscribers
      )
    );
    $queue->count_total = $queue->count_to_process = count($subscribers);
    $queue->status = null;
    $queue->save();
    // schedule newsletter for next delivery
    $new_queue = SendingQueue::create();
    $new_queue->newsletter_id = $newsletter->id;
    $new_queue->scheduled_at = $next_run_date;
    $new_queue->status = 'scheduled';
    $new_queue->save();
  }

  private function verifyMailPoetSubscriber($subscriber_id, $newsletter) {
    // check if subscriber is in proper segment
    $subscriber_in_segment =
      SubscriberSegment::where('subscriber_id', $subscriber_id)
        ->where('segment_id', $newsletter->segment)
        ->where('status', 'subscribed')
        ->findOne();
    return ($subscriber_in_segment) ? true : false;
  }

  private function verifyWPSubscriber($subscriber_id, $newsletter) {
    // check if user has the proper role
    $subscriber = Subscriber::findOne($subscriber_id);
    if(!$subscriber || $subscriber->wp_user_id === null) {
      return false;
    }
    $wp_user = (array) get_userdata($subscriber->wp_user_id);
    if(!in_array($newsletter->role, $wp_user['roles'])) {
      return false;
    }
    return true;
  }

  private function checkIfNewsletterChanged($newsletter) {
    $last_run_queue = SendingQueue::where('status', 'completed')
      ->where('newsletter_id', $newsletter->id)
      ->orderByDesc('id')
      ->findOne();
    if(!$last_run_queue) return true;
    $renderer = new Renderer($newsletter->asArray());
    $rendered_newsletter = $renderer->render();
    $new_hash = md5($rendered_newsletter['html']);
    $old_hash = $last_run_queue->newsletter_rendered_body_hash;
    return $new_hash !== $old_hash;
  }

  private function getQueueNextRunDate($schedule) {
    $schedule = Cron::factory($schedule);
    return $schedule->getNextRunDate(current_time('mysql'))
      ->format('Y-m-d H:i:s');
  }
}