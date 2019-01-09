<?php

namespace MailPoet\Cron\Workers\StatsNotifications;

use Carbon\Carbon;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Setting;
use MailPoet\Models\StatsNotification;

class Scheduler {

  /**
   * How many hours after the newsletter will be the stats notification sent
   * @var int
   */
  const HOURS_TO_SEND_AFTER_NEWSLETTER = 24;

  function schedule(Newsletter $newsletter) {
    if(!$this->shouldSchedule($newsletter)) {
      return false;
    }

    $task = ScheduledTask::create();
    $task->type = Worker::TASK_TYPE;
    $task->status = ScheduledTask::STATUS_SCHEDULED;
    $task->scheduled_at = $this->getNextRunDate();
    $task->save();

    $stats_notifications = StatsNotification::create();
    $stats_notifications->newsletter_id = $newsletter->id;
    $stats_notifications->task_id = $task->id;
    $stats_notifications->save();
  }

  private function shouldSchedule(Newsletter $newsletter) {
    if($this->isDisabled()) {
      return false;
    }
    if($this->isTaskScheduled($newsletter->id)) {
      return false;
    }
    if(($newsletter->type !== Newsletter::TYPE_NOTIFICATION) && ($newsletter->type !== Newsletter::TYPE_STANDARD)) {
      return false;
    }
    return true;
  }

  private function isDisabled() {
    $settings = Setting::getValue(Worker::SETTINGS_KEY);
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
      ->join(StatsNotification::$_table, 'tasks.id = notification.task_id', 'notification')
      ->where('tasks.type', Worker::TASK_TYPE)
      ->where('notification.newsletter_id', $newsletter_id)
      ->findMany();
    return (bool)$existing;
  }

  private function getNextRunDate() {
    $date = new Carbon();
    $date->addHours(self::HOURS_TO_SEND_AFTER_NEWSLETTER);
    return $date;
  }

}
