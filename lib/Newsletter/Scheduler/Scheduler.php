<?php
namespace MailPoet\Newsletter\Scheduler;

use Carbon\Carbon;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\NewsletterPost;
use MailPoet\Models\SendingQueue;

class Scheduler {
  const SECONDS_IN_HOUR = 3600;
  const LAST_WEEKDAY_FORMAT = 'L';
  const WORDPRESS_ALL_ROLES = 'mailpoet_all';
  const INTERVAL_IMMEDIATELY = 'immediately';
  const INTERVAL_IMMEDIATE = 'immediate';
  const INTERVAL_DAILY = 'daily';
  const INTERVAL_WEEKLY = 'weekly';
  const INTERVAL_MONTHLY = 'monthly';
  const INTERVAL_NTHWEEKDAY = 'nthWeekDay';

  static function schedulePostNotification($post_id) {
    $newsletters = self::getNewsletters(Newsletter::TYPE_NOTIFICATION);
    if(!count($newsletters)) return false;
    foreach($newsletters as $newsletter) {
      $post = NewsletterPost::where('newsletter_id', $newsletter->id)
        ->where('post_id', $post_id)
        ->findOne();
      if($post === false) {
        self::createPostNotificationQueue($newsletter);
      }
    }
  }

  static function scheduleSubscriberWelcomeNotification($subscriber_id, $segments) {
    $newsletters = self::getNewsletters(Newsletter::TYPE_WELCOME);
    if(empty($newsletters)) return false;
    foreach($newsletters as $newsletter) {
      if($newsletter->event === 'segment' &&
        in_array($newsletter->segment, $segments)
      ) {
        self::createWelcomeNotificationQueue($newsletter, $subscriber_id);
      }
    }
  }

  static function scheduleWPUserWelcomeNotification(
    $subscriber_id,
    $wp_user,
    $old_user_data = false
  ) {
    $newsletters = self::getNewsletters(Newsletter::TYPE_WELCOME);
    if(empty($newsletters)) return false;
    foreach($newsletters as $newsletter) {
      if($newsletter->event === 'user') {
        if(!empty($old_user_data['roles'])) {
          // do not schedule welcome newsletter if roles have not changed
          $old_role = $old_user_data['roles'];
          $new_role = $wp_user['roles'];
          if($newsletter->role === self::WORDPRESS_ALL_ROLES ||
            !array_diff($old_role, $new_role)
          ) {
            continue;
          }
        }
        if($newsletter->role === self::WORDPRESS_ALL_ROLES ||
          in_array($newsletter->role, $wp_user['roles'])
        ) {
          self::createWelcomeNotificationQueue($newsletter, $subscriber_id);
        }
      }
    }
  }

  static function createWelcomeNotificationQueue($newsletter, $subscriber_id) {
    $queue = SendingQueue::create();
    $queue->newsletter_id = $newsletter->id;
    $queue->subscribers = serialize(
      array(
        'to_process' => array($subscriber_id)
      )
    );
    $queue->count_total = $queue->count_to_process = 1;
    $after_time_type = $newsletter->afterTimeType;
    $after_time_number = $newsletter->afterTimeNumber;
    $scheduled_at = null;
    $current_time = Carbon::createFromTimestamp(current_time('timestamp'));
    switch($after_time_type) {
      case 'hours':
        $scheduled_at = $current_time->addHours($after_time_number);
        break;
      case 'days':
        $scheduled_at = $current_time->addDays($after_time_number);
        break;
      case 'weeks':
        $scheduled_at = $current_time->addWeeks($after_time_number);
        break;
      default:
        $scheduled_at = $current_time;
    }
    $queue->status = SendingQueue::STATUS_SCHEDULED;
    $queue->priority = SendingQueue::PRIORITY_HIGH;
    $queue->scheduled_at = $scheduled_at;
    return $queue->save();
  }

  static function createPostNotificationQueue($newsletter) {
    $next_run_date = self::getNextRunDate($newsletter->schedule);
    if(!$next_run_date) return;
    // do not schedule duplicate queues for the same time
    $existing_queue = SendingQueue::where('newsletter_id', $newsletter->id)
      ->where('scheduled_at', $next_run_date)
      ->findOne();
    if($existing_queue) return;
    $queue = SendingQueue::create();
    $queue->newsletter_id = $newsletter->id;
    $queue->status = SendingQueue::STATUS_SCHEDULED;
    $queue->scheduled_at = $next_run_date;
    $queue->save();
    return $queue;
  }

  static function processPostNotificationSchedule($newsletter) {
    $interval_type = $newsletter->intervalType;
    $hour = (int)$newsletter->timeOfDay / self::SECONDS_IN_HOUR;
    $week_day = $newsletter->weekDay;
    $month_day = $newsletter->monthDay;
    $nth_week_day = ($newsletter->nthWeekDay === self::LAST_WEEKDAY_FORMAT) ?
      $newsletter->nthWeekDay :
      '#' . $newsletter->nthWeekDay;
    switch($interval_type) {
      case self::INTERVAL_IMMEDIATELY:
        $schedule = '* * * * *';
        break;
      case self::INTERVAL_IMMEDIATE:
      case self::INTERVAL_DAILY:
        $schedule = sprintf('0 %s * * *', $hour);
        break;
      case self::INTERVAL_WEEKLY:
        $schedule = sprintf('0 %s * * %s', $hour, $week_day);
        break;
      case self::INTERVAL_NTHWEEKDAY:
        $schedule = sprintf('0 %s ? * %s%s', $hour, $week_day, $nth_week_day);
        break;
      case self::INTERVAL_MONTHLY:
        $schedule = sprintf('0 %s %s * *', $hour, $month_day);
        break;
    }
    $option_field = NewsletterOptionField::where('name', 'schedule')->findOne();
    $relation = NewsletterOption::where('newsletter_id', $newsletter->id)
      ->where('option_field_id', $option_field->id)
      ->findOne();
    if(!$relation) {
      $relation = NewsletterOption::create();
      $relation->newsletter_id = $newsletter->id;
      $relation->option_field_id = $option_field->id;
    }
    $relation->value = $schedule;
    $relation->save();
    return $relation->value;
  }

  static function getNextRunDate($schedule) {
    try {
      $schedule = \Cron\CronExpression::factory($schedule);
      $next_run_date = $schedule->getNextRunDate(Carbon::createFromTimestamp(current_time('timestamp')))
        ->format('Y-m-d H:i:s');
    } catch(\Exception $e) {
      $next_run_date = false;
    }
    return $next_run_date;
  }

  static function getNewsletters($type) {
    return Newsletter::getPublished()
      ->filter('filterType', $type)
      ->filter('filterStatus', Newsletter::STATUS_ACTIVE)
      ->filter('filterWithOptions')
      ->findMany();
  }

  static function formatDatetimeString($datetime_string) {
    return Carbon::parse($datetime_string)->format('Y-m-d H:i:s');
  }
}