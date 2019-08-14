<?php

namespace MailPoet\Newsletter\Scheduler;

use Carbon\Carbon;
use MailPoet\Logging\Logger;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\NewsletterPost;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Posts;

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

  static function transitionHook($new_status, $old_status, $post) {
    Logger::getLogger('post-notifications')->addInfo(
      'transition post notification hook initiated',
      [
        'post_id' => $post->ID,
        'new_status' => $new_status,
        'old_status' => $old_status,
      ]
    );
    $types = Posts::getTypes();
    if (($new_status !== 'publish') || !isset($types[$post->post_type])) {
      return;
    }
    self::schedulePostNotification($post->ID);
  }

  static function schedulePostNotification($post_id) {
    Logger::getLogger('post-notifications')->addInfo(
      'schedule post notification hook',
      ['post_id' => $post_id]
    );
    $newsletters = self::getNewsletters(Newsletter::TYPE_NOTIFICATION);
    if (!count($newsletters)) return false;
    foreach ($newsletters as $newsletter) {
      $post = NewsletterPost::where('newsletter_id', $newsletter->id)
        ->where('post_id', $post_id)
        ->findOne();
      if ($post === false) {
        self::createPostNotificationSendingTask($newsletter);
      }
    }
  }

  static function scheduleSubscriberWelcomeNotification($subscriber_id, $segments) {
    $newsletters = self::getNewsletters(Newsletter::TYPE_WELCOME);
    if (empty($newsletters)) return false;
    $result = [];
    foreach ($newsletters as $newsletter) {
      if ($newsletter->event === 'segment' &&
        in_array($newsletter->segment, $segments)
      ) {
        $result[] = self::createWelcomeNotificationSendingTask($newsletter, $subscriber_id);
      }
    }
    return $result;
  }

  static function scheduleAutomaticEmail($group, $event, $scheduling_condition = false, $subscriber_id = false, $meta = false) {
    $newsletters = self::getNewsletters(Newsletter::TYPE_AUTOMATIC, $group);
    if (empty($newsletters)) return false;
    foreach ($newsletters as $newsletter) {
      if ($newsletter->event !== $event) continue;
      if (is_callable($scheduling_condition) && !$scheduling_condition($newsletter)) continue;
      self::createAutomaticEmailSendingTask($newsletter, $subscriber_id, $meta);
    }
  }

  static function scheduleOrRescheduleAutomaticEmail($group, $event, $subscriber_id, $meta = false) {
    $newsletters = self::getNewsletters(Newsletter::TYPE_AUTOMATIC, $group);
    if (empty($newsletters)) {
      return false;
    }

    foreach ($newsletters as $newsletter) {
      if ($newsletter->event !== $event) {
        continue;
      }

      // try to find existing scheduled task for given subscriber
      $task = ScheduledTask::findOneScheduledByNewsletterIdAndSubscriberId($newsletter->id, $subscriber_id);
      if ($task) {
        self::rescheduleAutomaticEmailSendingTask($newsletter, $task);
      } else {
        self::createAutomaticEmailSendingTask($newsletter, $subscriber_id, $meta);
      }
    }
  }

  static function rescheduleAutomaticEmail($group, $event, $subscriber_id) {
    $newsletters = self::getNewsletters(Newsletter::TYPE_AUTOMATIC, $group);
    if (empty($newsletters)) {
      return false;
    }

    foreach ($newsletters as $newsletter) {
      if ($newsletter->event !== $event) {
        continue;
      }

      // try to find existing scheduled task for given subscriber
      $task = ScheduledTask::findOneScheduledByNewsletterIdAndSubscriberId($newsletter->id, $subscriber_id);
      if ($task) {
        self::rescheduleAutomaticEmailSendingTask($newsletter, $task);
      }
    }
  }

  static function cancelAutomaticEmail($group, $event, $subscriber_id) {
    $newsletters = self::getNewsletters(Newsletter::TYPE_AUTOMATIC, $group);
    if (empty($newsletters)) {
      return false;
    }

    foreach ($newsletters as $newsletter) {
      if ($newsletter->event !== $event) {
        continue;
      }

      // try to find existing scheduled task for given subscriber
      $task = ScheduledTask::findOneScheduledByNewsletterIdAndSubscriberId($newsletter->id, $subscriber_id);
      if ($task) {
        SendingQueue::where('task_id', $task->id)->deleteMany();
        ScheduledTaskSubscriber::where('task_id', $task->id)->deleteMany();
        $task->delete();
      }
    }
  }

  static function scheduleWPUserWelcomeNotification(
    $subscriber_id,
    $wp_user,
    $old_user_data = false
  ) {
    $newsletters = self::getNewsletters(Newsletter::TYPE_WELCOME);
    if (empty($newsletters)) return false;
    foreach ($newsletters as $newsletter) {
      if ($newsletter->event === 'user') {
        if (!empty($old_user_data['roles'])) {
          // do not schedule welcome newsletter if roles have not changed
          $old_role = $old_user_data['roles'];
          $new_role = $wp_user['roles'];
          if ($newsletter->role === self::WORDPRESS_ALL_ROLES ||
            !array_diff($old_role, $new_role)
          ) {
            continue;
          }
        }
        if ($newsletter->role === self::WORDPRESS_ALL_ROLES ||
          in_array($newsletter->role, $wp_user['roles'])
        ) {
          self::createWelcomeNotificationSendingTask($newsletter, $subscriber_id);
        }
      }
    }
  }

  static function createWelcomeNotificationSendingTask($newsletter, $subscriber_id) {
    $previously_scheduled_notification = SendingQueue::joinWithSubscribers()
      ->where('queues.newsletter_id', $newsletter->id)
      ->where('subscribers.subscriber_id', $subscriber_id)
      ->findOne();
    if (!empty($previously_scheduled_notification)) return;
    $sending_task = SendingTask::create();
    $sending_task->newsletter_id = $newsletter->id;
    $sending_task->setSubscribers([$subscriber_id]);
    $sending_task->status = SendingQueue::STATUS_SCHEDULED;
    $sending_task->priority = SendingQueue::PRIORITY_HIGH;
    $sending_task->scheduled_at = self::getScheduledTimeWithDelay(
      $newsletter->afterTimeType,
      $newsletter->afterTimeNumber
    );
    return $sending_task->save();
  }

  static function createAutomaticEmailSendingTask($newsletter, $subscriber_id, $meta) {
    $sending_task = SendingTask::create();
    $sending_task->newsletter_id = $newsletter->id;
    if ($newsletter->sendTo === 'user' && $subscriber_id) {
      $sending_task->setSubscribers([$subscriber_id]);
    }
    if ($meta) {
      $sending_task->__set('meta', $meta);
    }
    $sending_task->status = SendingQueue::STATUS_SCHEDULED;
    $sending_task->priority = SendingQueue::PRIORITY_MEDIUM;

    $sending_task->scheduled_at = self::getScheduledTimeWithDelay($newsletter->afterTimeType, $newsletter->afterTimeNumber);
    return $sending_task->save();
  }

  static function rescheduleAutomaticEmailSendingTask($newsletter, $task) {
    // compute new 'scheduled_at' from now
    $task->scheduled_at = self::getScheduledTimeWithDelay($newsletter->afterTimeType, $newsletter->afterTimeNumber);
    $task->save();
  }

  static function createPostNotificationSendingTask($newsletter) {
    $existing_notification_history = Newsletter::tableAlias('newsletters')
      ->where('newsletters.parent_id', $newsletter->id)
      ->where('newsletters.type', Newsletter::TYPE_NOTIFICATION_HISTORY)
      ->where('newsletters.status', Newsletter::STATUS_SENDING)
      ->join(
        MP_SENDING_QUEUES_TABLE,
        'queues.newsletter_id = newsletters.id',
        'queues'
      )
      ->join(
        MP_SCHEDULED_TASKS_TABLE,
        'queues.task_id = tasks.id',
        'tasks'
      )
      ->whereNotEqual('tasks.status', ScheduledTask::STATUS_PAUSED)
      ->findOne();
    if ($existing_notification_history) {
      return;
    }
    $next_run_date = self::getNextRunDate($newsletter->schedule);
    if (!$next_run_date) return;
    // do not schedule duplicate queues for the same time
    $existing_queue = SendingQueue::findTaskByNewsletterId($newsletter->id)
      ->where('tasks.scheduled_at', $next_run_date)
      ->findOne();
    if ($existing_queue) return;
    $sending_task = SendingTask::create();
    $sending_task->newsletter_id = $newsletter->id;
    $sending_task->status = SendingQueue::STATUS_SCHEDULED;
    $sending_task->scheduled_at = $next_run_date;
    $sending_task->save();
    Logger::getLogger('post-notifications')->addInfo(
      'schedule post notification',
      ['sending_task' => $sending_task->id(), 'scheduled_at' => $next_run_date]
    );
    return $sending_task;
  }

  static function processPostNotificationSchedule($newsletter) {
    $interval_type = $newsletter->intervalType;
    $hour = (int)$newsletter->timeOfDay / self::SECONDS_IN_HOUR;
    $week_day = $newsletter->weekDay;
    $month_day = $newsletter->monthDay;
    $nth_week_day = ($newsletter->nthWeekDay === self::LAST_WEEKDAY_FORMAT) ?
      $newsletter->nthWeekDay :
      '#' . $newsletter->nthWeekDay;
    switch ($interval_type) {
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
      case self::INTERVAL_IMMEDIATELY:
      default:
        $schedule = '* * * * *';
        break;
    }
    $option_field = NewsletterOptionField::where('name', 'schedule')->findOne();
    $relation = NewsletterOption::where('newsletter_id', $newsletter->id)
      ->where('option_field_id', $option_field->id)
      ->findOne();
    if (!$relation) {
      $relation = NewsletterOption::create();
      $relation->newsletter_id = $newsletter->id;
      $relation->option_field_id = $option_field->id;
    }
    $relation->value = $schedule;
    $relation->save();
    return $relation->value;
  }

  static function getNextRunDate($schedule, $from_timestamp = false) {
    $wp = new WPFunctions();
    $from_timestamp = ($from_timestamp) ? $from_timestamp : $wp->currentTime('timestamp');
    try {
      $schedule = \Cron\CronExpression::factory($schedule);
      $next_run_date = $schedule->getNextRunDate(Carbon::createFromTimestamp($from_timestamp))
        ->format('Y-m-d H:i:s');
    } catch (\Exception $e) {
      $next_run_date = false;
    }
    return $next_run_date;
  }

  static function getPreviousRunDate($schedule, $from_timestamp = false) {
    $wp = WPFunctions::get();
    $from_timestamp = ($from_timestamp) ? $from_timestamp : $wp->currentTime('timestamp');
    try {
      $schedule = \Cron\CronExpression::factory($schedule);
      $previous_run_date = $schedule->getPreviousRunDate(Carbon::createFromTimestamp($from_timestamp))
        ->format('Y-m-d H:i:s');
    } catch (\Exception $e) {
      $previous_run_date = false;
    }
    return $previous_run_date;
  }

  static function getScheduledTimeWithDelay($after_time_type, $after_time_number) {
    $wp = WPFunctions::get();
    $current_time = Carbon::createFromTimestamp($wp->currentTime('timestamp'));
    switch ($after_time_type) {
      case 'minutes':
        return $current_time->addMinutes($after_time_number);
      case 'hours':
        return $current_time->addHours($after_time_number);
      case 'days':
        return $current_time->addDays($after_time_number);
      case 'weeks':
        return $current_time->addWeeks($after_time_number);
      default:
        return $current_time;
    }
  }

  static function getNewsletters($type, $group = false) {
    return Newsletter::getPublished()
      ->filter('filterType', $type, $group)
      ->filter('filterStatus', Newsletter::STATUS_ACTIVE)
      ->filter('filterWithOptions', $type)
      ->findMany();
  }

  static function formatDatetimeString($datetime_string) {
    return Carbon::parse($datetime_string)->format('Y-m-d H:i:s');
  }
}
