<?php

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Logging\LoggerFactory;
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

  /** @var LoggerFactory */
  private $loggerFactory;

  public function __construct() {
    $this->loggerFactory = LoggerFactory::getInstance();
  }

  public function transitionHook($newStatus, $oldStatus, $post) {
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_POST_NOTIFICATIONS)->addInfo(
      'transition post notification hook initiated',
      [
        'post_id' => $post->ID,
        'new_status' => $newStatus,
        'old_status' => $oldStatus,
      ]
    );
    $types = Posts::getTypes();
    if (($newStatus !== 'publish') || !isset($types[$post->postType])) {
      return;
    }
    $this->schedulePostNotification($post->ID);
  }

  public function schedulePostNotification($postId) {
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_POST_NOTIFICATIONS)->addInfo(
      'schedule post notification hook',
      ['post_id' => $postId]
    );
    $newsletters = Scheduler::getNewsletters(Newsletter::TYPE_NOTIFICATION);
    if (!count($newsletters)) return false;
    foreach ($newsletters as $newsletter) {
      $post = NewsletterPost::where('newsletter_id', $newsletter->id)
        ->where('post_id', $postId)
        ->findOne();
      if ($post === false) {
        $this->createPostNotificationSendingTask($newsletter);
      }
    }
  }

  public function createPostNotificationSendingTask($newsletter) {
    $existingNotificationHistory = Newsletter::tableAlias('newsletters')
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
    if ($existingNotificationHistory) {
      return;
    }
    $nextRunDate = Scheduler::getNextRunDate($newsletter->schedule);
    if (!$nextRunDate) return;
    // do not schedule duplicate queues for the same time
    $existingQueue = SendingQueue::findTaskByNewsletterId($newsletter->id)
      ->where('tasks.scheduled_at', $nextRunDate)
      ->findOne();
    if ($existingQueue) return;
    $sendingTask = SendingTask::create();
    $sendingTask->newsletterId = $newsletter->id;
    $sendingTask->status = SendingQueue::STATUS_SCHEDULED;
    $sendingTask->scheduledAt = $nextRunDate;
    $sendingTask->save();
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_POST_NOTIFICATIONS)->addInfo(
      'schedule post notification',
      ['sending_task' => $sendingTask->id(), 'scheduled_at' => $nextRunDate]
    );
    return $sendingTask;
  }

  public function processPostNotificationSchedule($newsletter) {
    $intervalType = $newsletter->intervalType;
    $hour = (int)$newsletter->timeOfDay / self::SECONDS_IN_HOUR;
    $weekDay = $newsletter->weekDay;
    $monthDay = $newsletter->monthDay;
    $nthWeekDay = ($newsletter->nthWeekDay === self::LAST_WEEKDAY_FORMAT) ?
      $newsletter->nthWeekDay :
      '#' . $newsletter->nthWeekDay;
    switch ($intervalType) {
      case self::INTERVAL_IMMEDIATE:
      case self::INTERVAL_DAILY:
        $schedule = sprintf('0 %s * * *', $hour);
        break;
      case self::INTERVAL_WEEKLY:
        $schedule = sprintf('0 %s * * %s', $hour, $weekDay);
        break;
      case self::INTERVAL_NTHWEEKDAY:
        $schedule = sprintf('0 %s ? * %s%s', $hour, $weekDay, $nthWeekDay);
        break;
      case self::INTERVAL_MONTHLY:
        $schedule = sprintf('0 %s %s * *', $hour, $monthDay);
        break;
      case self::INTERVAL_IMMEDIATELY:
      default:
        $schedule = '* * * * *';
        break;
    }
    $relation = null;
    $optionField = NewsletterOptionField::where('name', 'schedule')->findOne();
    if ($optionField instanceof NewsletterOptionField) {
      $relation = NewsletterOption::where('newsletter_id', $newsletter->id)
        ->where('option_field_id', $optionField->id)
        ->findOne();
    } else {
      throw new \Exception('NewsletterOptionField for schedule doesn’t exist.');
    }
    if (!$relation instanceof NewsletterOption) {
      $relation = NewsletterOption::create();
      $relation->newsletterId = $newsletter->id;
      $relation->optionFieldId = (int)$optionField->id;
    }
    $relation->value = $schedule;
    $relation->save();
    return $relation->value;
  }
}
