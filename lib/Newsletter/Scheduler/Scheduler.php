<?php
namespace MailPoet\Newsletter\Scheduler;

use Carbon\Carbon;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;

class Scheduler {
  static function newSegmentSubscriptionNewsletter(array $subscriber, array $segments) {
    $newsletters = self::getWelcomeNewsletters();
    if(!count($newsletters)) return;
    foreach($newsletters as $newsletter) {
      if($newsletter['event'] === 'segment' &&
        in_array($newsletter['segment'], $segments)
      ) {
        self::scheduleWelcomeNewsletter($newsletter, $subscriber);
      }
    }
  }

  static function newUserRegistrationNewsletter(array $subscriber, array $wp_user) {
    $newsletters = self::getWelcomeNewsletters();
    if(!count($newsletters)) return;
    foreach($newsletters as $newsletter) {
      if($newsletter['event'] === 'user' &&
        in_array($newsletter['role'], $wp_user['roles'])
      ) {
        self::scheduleWelcomeNewsletter($newsletter, $subscriber);
      }
    }
  }

  private static function getWelcomeNewsletters() {
    return Newsletter::where('type', 'welcome')
      ->filter('filterWithOptions')
      ->findArray();
  }

  private static function scheduleWelcomeNewsletter($newsletter, $subscriber) {
    $queue = SendingQueue::create();
    $queue->newsletter_id = $newsletter['id'];
    $queue->subscribers = serialize(
      array(
        'to_process' => array($subscriber['id'])
      )
    );
    $queue->count_total = $queue->count_to_process = 1;
    $after_time_type = $newsletter['afterTimeType'];
    $after_time_number = $newsletter['afterTimeNumber'];
    $scheduled_at = null;
    switch($after_time_type) {
      case 'hours':
        $scheduled_at = Carbon::now()
          ->addHours($after_time_number);
        break;
      case 'days':
        $scheduled_at = Carbon::now()
          ->addDays($after_time_number);
        break;
      case 'weeks':
        $scheduled_at = Carbon::now()
          ->addWeeks($after_time_number);
        break;
    }
    if($scheduled_at) {
      $queue->status = 'scheduled';
      $queue->scheduled_at = $scheduled_at;
    }
    $queue->save();
  }
}