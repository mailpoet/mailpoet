<?php

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Logging\Logger;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\NewsletterPost;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Posts;

class PostNotificationScheduler {

  const SECONDS_IN_HOUR = 3600;
  const LAST_WEEKDAY_FORMAT = 'L';
  const INTERVAL_DAILY = 'daily';
  const INTERVAL_IMMEDIATELY = 'immediately';
  const INTERVAL_NTHWEEKDAY = 'nthWeekDay';
  const INTERVAL_WEEKLY = 'weekly';
  const INTERVAL_IMMEDIATE = 'immediate';
  const INTERVAL_MONTHLY = 'monthly';

  function transitionHook($new_status, $old_status, $post) {
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
    $this->schedulePostNotification($post->ID);
  }

  function schedulePostNotification($post_id) {
    Logger::getLogger('post-notifications')->addInfo(
      'schedule post notification hook',
      ['post_id' => $post_id]
    );
    $newsletters = Scheduler::getNewsletters(Newsletter::TYPE_NOTIFICATION);
    if (!count($newsletters)) return false;
    foreach ($newsletters as $newsletter) {
      $post = NewsletterPost::where('newsletter_id', $newsletter->id)
        ->where('post_id', $post_id)
        ->findOne();
      if ($post === false) {
        $this->createPostNotificationSendingTask($newsletter);
      }
    }
  }

  function createPostNotificationSendingTask($newsletter) {
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
    $next_run_date = Scheduler::getNextRunDate($newsletter->schedule);
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

  function processPostNotificationSchedule($newsletter) {
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

}
