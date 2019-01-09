<?php

namespace MailPoet\Cron\Workers\StatsNotifications;

use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Setting;
use MailPoet\Models\StatsNotification;

class SchedulerTest extends \MailPoetTest {

  /** @var Scheduler */
  private $stats_notifications;

  function _before() {
    $this->stats_notifications = new Scheduler();
    Setting::setValue(Worker::SETTINGS_KEY, [
      'enabled' => true,
      'address' => 'email@example.com'
    ]);
  }

  function testShouldSchedule() {
    $newsletter_id = 5;
    $newsletter = Newsletter::createOrUpdate(['id' => $newsletter_id, 'type' => Newsletter::TYPE_STANDARD]);
    $this->stats_notifications->schedule($newsletter);
    $notification = StatsNotification::where('newsletter_id', $newsletter_id)->findOne();
    expect($notification)->isInstanceOf(StatsNotification::class);
    $task = ScheduledTask::where('id', $notification->task_id)->findOne();
    expect($task)->isInstanceOf(ScheduledTask::class);
  }

  function testShouldNotScheduleIfDisabled() {
    $newsletter_id = 6;
    Setting::setValue(Worker::SETTINGS_KEY, [
      'enabled' => false,
      'address' => 'email@example.com'
    ]);
    $newsletter = Newsletter::createOrUpdate(['id' => $newsletter_id, 'type' => Newsletter::TYPE_STANDARD]);
    $this->stats_notifications->schedule($newsletter);
    $notification = StatsNotification::where('newsletter_id', $newsletter_id)->findOne();
    expect($notification)->isEmpty();
  }

  function testShouldNotScheduleIfSettingsMissing() {
    $newsletter_id = 7;
    Setting::setValue(Worker::SETTINGS_KEY, []);
    $newsletter = Newsletter::createOrUpdate(['id' => $newsletter_id, 'type' => Newsletter::TYPE_STANDARD]);
    $this->stats_notifications->schedule($newsletter);
    $notification = StatsNotification::where('newsletter_id', $newsletter_id)->findOne();
    expect($notification)->isEmpty();
  }

  function testShouldNotScheduleIfEmailIsMissing() {
    $newsletter_id = 8;
    Setting::setValue(Worker::SETTINGS_KEY, [
      'enabled' => true,
    ]);
    $newsletter = Newsletter::createOrUpdate(['id' => $newsletter_id, 'type' => Newsletter::TYPE_STANDARD]);
    $this->stats_notifications->schedule($newsletter);
    $notification = StatsNotification::where('newsletter_id', $newsletter_id)->findOne();
    expect($notification)->isEmpty();
  }

  function testShouldNotScheduleIfEmailIsEmpty() {
    $newsletter_id = 9;
    Setting::setValue(Worker::SETTINGS_KEY, [
      'enabled' => true,
      'address' => ' '
    ]);
    $newsletter = Newsletter::createOrUpdate(['id' => $newsletter_id, 'type' => Newsletter::TYPE_STANDARD]);
    $this->stats_notifications->schedule($newsletter);
    $notification = StatsNotification::where('newsletter_id', $newsletter_id)->findOne();
    expect($notification)->isEmpty();
  }

  function testShouldNotScheduleIfAlreadyScheduled() {
    $newsletter_id = 10;
    $existing_task = ScheduledTask::createOrUpdate([
      'type' => Worker::TASK_TYPE,
      'status' => ScheduledTask::STATUS_SCHEDULED,
      'scheduled_at' => '2017-01-02 12:13:14',
    ]);
    $existing_notification = StatsNotification::createOrUpdate([
      'newsletter_id' => $newsletter_id,
      'task_id' => $existing_task->id,
    ]);
    $newsletter = Newsletter::createOrUpdate(['id' => $newsletter_id, 'type' => Newsletter::TYPE_STANDARD]);
    $this->stats_notifications->schedule($newsletter);
    $notifications = StatsNotification::where('newsletter_id', $newsletter_id)->findMany();
    expect($notifications)->count(1);
    $tasks = ScheduledTask::where('id', $notifications[0]->task_id)->findMany();
    expect($tasks)->count(1);
    expect($existing_notification->id)->equals($notifications[0]->id);
    expect($existing_task->id)->equals($tasks[0]->id);
  }

  function testShouldNotScheduleIfInvalidType() {
    $newsletter_id = 11;
    $newsletter = Newsletter::createOrUpdate(['id' => $newsletter_id, Newsletter::TYPE_WELCOME]);
    $this->stats_notifications->schedule($newsletter);
    $notification = StatsNotification::where('newsletter_id', $newsletter_id)->findOne();
    expect($notification)->isEmpty();
  }

}
