<?php

namespace MailPoet\Cron\Workers;

use Carbon\Carbon;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Setting;


/**
 * TODO:
 * - finish stats_notifications table, test if it is working and the migration is creating the table
 * - remove all the SendingQueue from here
 * - in schedule method add a row to stats_notifications table
 * - when sending of post notification or a standard newsletter is finished call schedule
 * - add processing of this task to Daemon
 * - check JIRA what to do next and how to send the newsletter
 * - see \MailPoet\Subscribers\NewSubscriberNotificationMailer how to send an email, now with DI everything should be easy
 */

class StatsNotifications {

  const TASK_TYPE = 'stats_notification';
  const SETTINGS_KEY = 'stats_notifications';

  /**
   * How many hours after the newsletter will be the stats notification sent
   * @var int
   */
  const HOURS_TO_SEND_AFTER_NEWSLETTER = 24;

  function schedule($newsletter_id) {
    if(!$this->shouldSchedule($newsletter_id)) {
      return false;
    }

    $task = ScheduledTask::create();
    $task->type = self::TASK_TYPE;
    $task->status = ScheduledTask::STATUS_SCHEDULED;
    $task->scheduled_at = $this->getNextRunDate();
    $task->save();

    $queue = SendingQueue::create();
    $queue->newsletter_id = $newsletter_id;
    $queue->task_id = $task->id;
    $queue->save();
  }

  function process() {

  }

  private function shouldSchedule($newsletter_id) {
    if($this->isDisabled()) {
      return false;
    }
    if($this->isTaskScheduled($newsletter_id)) {
      return false;
    }
    return true;
  }

  private function isDisabled() {
    $settings = Setting::getValue(self::SETTINGS_KEY);
    if(!is_array($settings)) {
      return true;
    }
    if(!isset($settings['enabled'])) {
      return true;
    }
    if(!isset($settings['address'])) {
      return true;
    }
    if(empty(trim($settings['address']))) {
      return true;
    }
    return !(bool)$settings['enabled'];
  }

  private function isTaskScheduled($newsletter_id) {
    $existing = ScheduledTask::table_alias('tasks')
      ->join(SendingQueue::$_table, 'tasks.id = queues.task_id', 'queues')
      ->where('tasks.type', self::TASK_TYPE)
      ->where('queues.newsletter_id', $newsletter_id)
      ->findMany();
    return (bool) $existing;
  }

  private function getNextRunDate() {
    $date = new Carbon();
    $date->addHours(self::HOURS_TO_SEND_AFTER_NEWSLETTER);
    return $date;
  }

}
