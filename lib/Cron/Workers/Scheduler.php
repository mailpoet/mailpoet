<?php
namespace MailPoet\Cron\Workers;

use Carbon\Carbon;
use MailPoet\Cron\CronHelper;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\SubscriberSegment;

if(!defined('ABSPATH')) exit;

class Scheduler {
  public $timer;

  function __construct($timer = false) {
    $this->timer = ($timer) ? $timer : microtime(true);
    CronHelper::checkExecutionTimer($this->timer);
  }

  function process() {
    $this->processScheduledNewsletters();
  }

  function processScheduledQueues() {
    $scheduled_queues = SendingQueue::where('status', 'scheduled')
      ->whereLte('scheduled_at', Carbon::now()
        ->format('Y-m-d H:i:s'))
      ->findMany();
    if(!count($scheduled_queues)) return;
    foreach($scheduled_queues as $queue) {
      $newsletter = Newsletter::filter('filterWithOptions')
        ->findOne($queue->newsletter_id)
        ->asArray();
      $subscriber = unserialize($queue->subscribers);
      $subscriber_in_segment =
        SubscriberSegment::where('subscriber_id', $subscriber['to_process'][0])
          ->where('segment_id', $newsletter['segment'])
          ->findOne();
      if(!$subscriber_in_segment) {
        $queue->delete();
      } else {
        $queue->status = null;
        $queue->save();
      }
      CronHelper::checkExecutionTimer($this->timer);
    }
  }
}