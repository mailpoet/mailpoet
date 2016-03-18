<?php
namespace MailPoet\Newsletter\Scheduler;

use Carbon\Carbon;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\SendingQueue;

class Scheduler {
  const seconds_in_hour = 3600;
  const last_weekday_format = 'L';

  static function postNotification($newsletter_id) {
    $newsletter = Newsletter::filter('filterWithOptions')
      ->findOne($newsletter_id)
      ->asArray();
    $interval_type = $newsletter['intervalType'];
    $hour = (int) $newsletter['timeOfDay'] / self::seconds_in_hour;
    $week_day = $newsletter['weekDay'];
    $month_day = $newsletter['monthDay'];
    $nth_week_day = ($newsletter['nthWeekDay'] === self::last_weekday_format) ?
      $newsletter['nthWeekDay'] :
      '#' . $newsletter['nthWeekDay'];
    switch($interval_type) {
      case 'immediately':
        $cron = '* * * * *';
        break;
      case 'immediate': //daily
        $cron = sprintf('0 %s * * *', $hour);
        break;
      case 'weekly':
        $cron = sprintf('0 %s * * %s', $hour, $week_day);
        break;
      case 'monthly':
        $cron = sprintf('0 %s %s * *', $hour, $month_day);
        break;
      case 'nthWeekDay':
        $cron = sprintf('0 %s ? * %s%s', $hour, $week_day, $nth_week_day);
        break;
    }
    $option_field = NewsletterOptionField::where('name', 'schedule')
      ->findOne()
      ->asArray();
    $relation = NewsletterOption::create();
    $relation->newsletter_id = $newsletter['id'];
    $relation->option_field_id = $option_field['id'];
    $relation->value = $cron;
    $relation->save();
  }

  static function welcomeForSegmentSubscription($subscriber_id, array $segments) {
    $newsletters = self::getWelcomeNewsletters();
    if(!count($newsletters)) return;
    foreach($newsletters as $newsletter) {
      if($newsletter['event'] === 'segment' &&
        in_array($newsletter['segment'], $segments)
      ) {
        self::createSendingQueueEntry($newsletter, $subscriber_id);
      }
    }
  }

  static function welcomeForNewWPUser($subscriber_id, array $wp_user) {
    $newsletters = self::getWelcomeNewsletters();
    if(!count($newsletters)) return;
    foreach($newsletters as $newsletter) {
      if($newsletter['event'] === 'user' &&
        in_array($newsletter['role'], $wp_user['roles'])
      ) {
        self::createSendingQueueEntry($newsletter, $subscriber_id);
      }
    }
  }

  private static function getWelcomeNewsletters() {
    return Newsletter::where('type', 'welcome')
      ->filter('filterWithOptions')
      ->findArray();
  }

  private static function createSendingQueueEntry($newsletter, $subscriber_id) {
    $queue = SendingQueue::create();
    $queue->newsletter_id = $newsletter['id'];
    $queue->subscribers = serialize(
      array(
        'to_process' => array($subscriber_id)
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