<?php

namespace MailPoet\Newsletter\Scheduler;

use Carbon\Carbon;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Functions as WPFunctions;

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
