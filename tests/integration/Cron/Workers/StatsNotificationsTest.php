<?php

namespace MailPoet\Test\Cron\Workers;

use Carbon\Carbon;
use MailPoet\Cron\Workers\StatsNotifications;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Setting;

class StatsNotificationsTest extends \MailPoetTest {

  /** @var StatsNotifications */
  private $stats_notifications;

  function _before() {
    $this->stats_notifications = new StatsNotifications();
    Setting::setValue(StatsNotifications::SETTINGS_KEY, [
      'enabled' => true,
      'address' => 'email@example.com'
    ]);
  }

  function testShouldSchedule() {
    $newsletter_id = 5;
    $this->stats_notifications->schedule($newsletter_id);
    $queue = SendingQueue::where('newsletter_id', $newsletter_id)->findOne();
    expect($queue)->isInstanceOf(SendingQueue::class);
    $task = ScheduledTask::where('id', $queue->task_id)->findOne();
    expect($task)->isInstanceOf(ScheduledTask::class);
  }

  function testShouldNotScheduleIfDisabled() {
    $newsletter_id = 6;
    Setting::setValue(StatsNotifications::SETTINGS_KEY, [
      'enabled' => false,
      'address' => 'email@example.com'
    ]);
    $this->stats_notifications->schedule($newsletter_id);
    $queue = SendingQueue::where('newsletter_id', $newsletter_id)->findOne();
    expect($queue)->isEmpty();
  }

  function testShouldNotScheduleIfSettingsMissing() {
    $newsletter_id = 7;
    Setting::setValue(StatsNotifications::SETTINGS_KEY, []);
    $this->stats_notifications->schedule($newsletter_id);
    $queue = SendingQueue::where('newsletter_id', $newsletter_id)->findOne();
    expect($queue)->isEmpty();
  }

  function testShouldNotScheduleIfEmailIsMissing() {
    $newsletter_id = 8;
    Setting::setValue(StatsNotifications::SETTINGS_KEY, [
      'enabled' => true,
    ]);
    $this->stats_notifications->schedule($newsletter_id);
    $queue = SendingQueue::where('newsletter_id', $newsletter_id)->findOne();
    expect($queue)->isEmpty();
  }

  function testShouldNotScheduleIfEmailIsEmpty() {
    $newsletter_id = 9;
    Setting::setValue(StatsNotifications::SETTINGS_KEY, [
      'enabled' => true,
      'address' => ' '
    ]);
    $this->stats_notifications->schedule($newsletter_id);
    $queue = SendingQueue::where('newsletter_id', $newsletter_id)->findOne();
    expect($queue)->isEmpty();
  }

  function testShouldNotScheduleIfAlreadyScheduled() {
    $newsletter_id = 10;
    $existing_task = ScheduledTask::createOrUpdate([
      'type' => StatsNotifications::TASK_TYPE,
      'status' => ScheduledTask::STATUS_SCHEDULED,
      'scheduled_at' => '2017-01-02 12:13:14',
    ]);
    $existing_queue = SendingQueue::createOrUpdate([
      'newsletter_id' => $newsletter_id,
      'task_id' => $existing_task->id,
    ]);
    $this->stats_notifications->schedule($newsletter_id);
    $queues = SendingQueue::where('newsletter_id', $newsletter_id)->findMany();
    expect($queues)->count(1);
    $tasks = ScheduledTask::where('id', $queues[0]->task_id)->findMany();
    expect($tasks)->count(1);
    expect($existing_queue->id)->equals($queues[0]->id);
    expect($existing_task->id)->equals($tasks[0]->id);
  }

}
