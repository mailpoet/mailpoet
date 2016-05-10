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

  static function processPostNotificationSchedule($newsletter_id) {
    $newsletter = Newsletter::filter('filterWithOptions')
      ->findOne($newsletter_id);
    if(!$newsletter) return;
    $newsletter = $newsletter->asArray();
    $interval_type = $newsletter['intervalType'];
    $hour = (int) $newsletter['timeOfDay'] / self::SECONDS_IN_HOUR;
    $week_day = $newsletter['weekDay'];
    $month_day = $newsletter['monthDay'];
    $nth_week_day = ($newsletter['nthWeekDay'] === self::LAST_WEEKDAY_FORMAT) ?
      $newsletter['nthWeekDay'] :
      '#' . $newsletter['nthWeekDay'];
    switch($interval_type) {
      case 'immediately':
        $schedule = '* * * * *';
        break;
      case 'immediate':
      case 'daily':
        $schedule = sprintf('0 %s * * *', $hour);
        break;
      case 'weekly':
        $schedule = sprintf('0 %s * * %s', $hour, $week_day);
        break;
      case 'monthly':
        $schedule = sprintf('0 %s %s * *', $hour, $month_day);
        break;
      case 'nthWeekDay':
        $schedule = sprintf('0 %s ? * %s%s', $hour, $week_day, $nth_week_day);
        break;
    }
    $option_field = NewsletterOptionField::where('name', 'schedule')
      ->findOne()
      ->asArray();
    $relation = NewsletterOption::where('newsletter_id', $newsletter_id)
      ->where('option_field_id', $option_field['id'])
      ->findOne();
    if(!$relation) {
      $relation = NewsletterOption::create();
      $relation->newsletter_id = $newsletter['id'];
      $relation->option_field_id = $option_field['id'];
    }
    $relation->value = $schedule;
    $relation->save();
  }

  static function schedulePostNotification($post_id) {
    $newsletters = self::getNewsletters('notification');
    if(!count($newsletters)) return;
    foreach($newsletters as $newsletter) {
      $post = NewsletterPost::where('newsletter_id', $newsletter['id'])
        ->where('post_id', $post_id)
        ->findOne();
      if($post === false) {
        $scheduled_notification = self::createPostNotificationQueue($newsletter);
      }
    }
  }

  static function scheduleSubscriberWelcomeNotification(
    $subscriber_id,
    array $segments
  ) {
    $newsletters = self::getNewsletters('welcome');
    if(!count($newsletters)) return;
    foreach($newsletters as $newsletter) {
      if($newsletter['event'] === 'segment' &&
        in_array($newsletter['segment'], $segments)
      ) {
        self::createWelcomeNotificationQueue($newsletter, $subscriber_id);
      }
    }
  }

  static function scheduleWPUserWelcomeNotification(
    $subscriber_id,
    array $wp_user,
    $old_user_data
  ) {
    $newsletters = self::getNewsletters('welcome');
    if(!count($newsletters)) return;
    foreach($newsletters as $newsletter) {
      if($newsletter['event'] === 'user') {
        if($old_user_data) {
          // do not schedule welcome newsletter if roles have not changed
          $old_role = (array) $old_user_data->roles;
          $new_role = (array) $wp_user->roles;
          if($newsletter['role'] === self::WORDPRESS_ALL_ROLES ||
            !array_diff($old_role, $new_role)
          ) {
            continue;
          }
        }
        if($newsletter['role'] === self::WORDPRESS_ALL_ROLES ||
          in_array($newsletter['role'], $wp_user['roles'])
        ) {
          self::createWelcomeNotificationQueue($newsletter, $subscriber_id);
        }
      }
    }
  }

  static function getNewsletters($type) {
    return Newsletter::where('type', $type)
      ->whereNull('deleted_at')
      ->filter('filterWithOptions')
      ->findArray();
  }

  static function createWelcomeNotificationQueue($newsletter, $subscriber_id) {
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
    $queue->status = 'scheduled';
    $queue->scheduled_at = $scheduled_at;
    $queue->save();
  }

  static function createPostNotificationQueue($newsletter) {
    $next_run_date = self::getNextRunDate($newsletter['schedule']);
    // do not schedule duplicate queues for the same time
    $existing_queue = SendingQueue::where('newsletter_id', $newsletter['id'])
      ->where('scheduled_at', $next_run_date)
      ->findOne();
    if($existing_queue) return;
    $queue = SendingQueue::create();
    $queue->newsletter_id = $newsletter['id'];
    $queue->status = 'scheduled';
    $queue->scheduled_at = $next_run_date;
    $queue->save();
    return $queue;
  }

  static function getNextRunDate($schedule) {
    $schedule = \Cron\CronExpression::factory($schedule);
    return $schedule->getNextRunDate(current_time('mysql'))
      ->format('Y-m-d H:i:s');
  }
}