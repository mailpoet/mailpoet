<?php

namespace MailPoet\Newsletter\Scheduler;

use Carbon\Carbon;
use MailPoet\Config\Hooks;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\NewsletterPost;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Posts as WPPosts;

class PostNotificationTest extends \MailPoetTest {

  /** @var PostNotificationScheduler */
  private $post_notification_scheduler;

  function _before() {
    parent::_before();
    $this->post_notification_scheduler = new PostNotificationScheduler;
  }

  function testItCreatesPostNotificationSendingTask() {
    $newsletter = $this->_createNewsletter();
    $newsletter->schedule = '* 5 * * *';

    // new queue record should be created
    $queue = $this->post_notification_scheduler->createPostNotificationSendingTask($newsletter);
    expect(SendingQueue::findMany())->count(1);
    expect($queue->newsletter_id)->equals($newsletter->id);
    expect($queue->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect($queue->scheduled_at)->equals(Scheduler::getNextRunDate('* 5 * * *'));
    expect($queue->priority)->equals(SendingQueue::PRIORITY_MEDIUM);

    // duplicate queue record should not be created
    $this->post_notification_scheduler->createPostNotificationSendingTask($newsletter);
    expect(SendingQueue::findMany())->count(1);
  }

  function testItCreatesPostNotificationSendingTaskIfAPausedNotificationExists() {
    $newsletter = $this->_createNewsletter();
    $newsletter->schedule = '* 5 * * *';

    // new queue record should be created
    $queue_to_be_paused = $this->post_notification_scheduler->createPostNotificationSendingTask($newsletter);
    $queue_to_be_paused->task()->pause();

    // another queue record should be created because the first one was paused
    $newsletter->schedule = '* 10 * * *'; // different time to not clash with the first queue
    $queue = $this->post_notification_scheduler->createPostNotificationSendingTask($newsletter);
    expect(SendingQueue::findMany())->count(2);
    expect($queue->newsletter_id)->equals($newsletter->id);
    expect($queue->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect($queue->scheduled_at)->equals(Scheduler::getNextRunDate('* 10 * * *'));
    expect($queue->priority)->equals(SendingQueue::PRIORITY_MEDIUM);

    // duplicate queue record should not be created
    $this->post_notification_scheduler->createPostNotificationSendingTask($newsletter);
    expect(SendingQueue::findMany())->count(2);
  }

  function tesIttDoesNotSchedulePostNotificationWhenNotificationWasAlreadySentForPost() {
    $newsletter = $this->_createNewsletter();
    $newsletter_post = NewsletterPost::create();
    $newsletter_post->newsletter_id = $newsletter->id;
    $newsletter_post->post_id = 10;
    $newsletter_post->save();

    // queue is not created when notification was already sent for the post
    $this->post_notification_scheduler->schedulePostNotification($post_id = 10);
    $queue = SendingQueue::findTaskByNewsletterId($newsletter->id)
      ->findOne();
    expect($queue)->false();
  }

  function testItSchedulesPostNotification() {
    $newsletter = $this->_createNewsletter();
    $this->_createNewsletterOptions(
      $newsletter->id,
      [
        'schedule' => '0 5 * * *',
      ]
    );

    // queue is created and scheduled for delivery one day later at 5 a.m.
    $this->post_notification_scheduler->schedulePostNotification($post_id = 10);
    $current_time = Carbon::createFromTimestamp(current_time('timestamp'));
    Carbon::setTestNow($current_time); // mock carbon to return current time
    $next_run_date = ($current_time->hour < 5) ?
      $current_time :
      $current_time->addDay();
    $queue = SendingQueue::findTaskByNewsletterId($newsletter->id)
      ->findOne();
    expect($queue->scheduled_at)->startsWith($next_run_date->format('Y-m-d 05:00'));
  }

  function testItProcessesPostNotificationScheduledForDailyDelivery() {
    $newsletter_option_field = NewsletterOptionField::create();
    $newsletter_option_field->name = 'schedule';
    $newsletter_option_field->newsletter_type = Newsletter::TYPE_NOTIFICATION;
    $newsletter_option_field->save();

    // daily notification is scheduled at 14:00
    $newsletter = (object)[
      'id' => 1,
      'intervalType' => Scheduler::INTERVAL_DAILY,
      'monthDay' => null,
      'nthWeekDay' => null,
      'weekDay' => null,
      'timeOfDay' => 50400, // 2 p.m.
    ];
    $this->post_notification_scheduler->processPostNotificationSchedule($newsletter);
    $newsletter_option = NewsletterOption::where('newsletter_id', $newsletter->id)
      ->where('option_field_id', $newsletter_option_field->id)
      ->findOne();
    $current_time = 1483275600; // Sunday, 1 January 2017 @ 1:00pm (UTC)
    expect(Scheduler::getNextRunDate($newsletter_option->value, $current_time))
      ->equals('2017-01-01 14:00:00');
  }

  function testItProcessesPostNotificationScheduledForWeeklyDelivery() {
    $newsletter_option_field = NewsletterOptionField::create();
    $newsletter_option_field->name = 'schedule';
    $newsletter_option_field->newsletter_type = Newsletter::TYPE_NOTIFICATION;
    $newsletter_option_field->save();

    // weekly notification is scheduled every Tuesday at 14:00
    $newsletter = (object)[
      'id' => 1,
      'intervalType' => Scheduler::INTERVAL_WEEKLY,
      'monthDay' => null,
      'nthWeekDay' => null,
      'weekDay' => Carbon::TUESDAY,
      'timeOfDay' => 50400, // 2 p.m.
    ];
    $this->post_notification_scheduler->processPostNotificationSchedule($newsletter);
    $newsletter_option = NewsletterOption::where('newsletter_id', $newsletter->id)
      ->where('option_field_id', $newsletter_option_field->id)
      ->findOne();
    $current_time = 1483275600; // Sunday, 1 January 2017 @ 1:00pm (UTC)
    expect(Scheduler::getNextRunDate($newsletter_option->value, $current_time))
      ->equals('2017-01-03 14:00:00');
  }

  function testItProcessesPostNotificationScheduledForMonthlyDeliveryOnSpecificDay() {
    $newsletter_option_field = NewsletterOptionField::create();
    $newsletter_option_field->name = 'schedule';
    $newsletter_option_field->newsletter_type = Newsletter::TYPE_NOTIFICATION;
    $newsletter_option_field->save();

    // monthly notification is scheduled every 20th day at 14:00
    $newsletter = (object)[
      'id' => 1,
      'intervalType' => Scheduler::INTERVAL_MONTHLY,
      'monthDay' => 19, // 20th (count starts from 0)
      'nthWeekDay' => null,
      'weekDay' => null,
      'timeOfDay' => 50400,// 2 p.m.
    ];
    $this->post_notification_scheduler->processPostNotificationSchedule($newsletter);
    $newsletter_option = NewsletterOption::where('newsletter_id', $newsletter->id)
      ->where('option_field_id', $newsletter_option_field->id)
      ->findOne();
    $current_time = 1483275600; // Sunday, 1 January 2017 @ 1:00pm (UTC)
    expect(Scheduler::getNextRunDate($newsletter_option->value, $current_time))
      ->equals('2017-01-19 14:00:00');
  }

  function testItProcessesPostNotificationScheduledForMonthlyDeliveryOnLastWeekDay() {
    $newsletter_option_field = NewsletterOptionField::create();
    $newsletter_option_field->name = 'schedule';
    $newsletter_option_field->newsletter_type = Newsletter::TYPE_NOTIFICATION;
    $newsletter_option_field->save();

    // monthly notification is scheduled every last Saturday at 14:00
    $newsletter = (object)[
      'id' => 1,
      'intervalType' => Scheduler::INTERVAL_NTHWEEKDAY,
      'monthDay' => null,
      'nthWeekDay' => 'L', // L = last
      'weekDay' => Carbon::SATURDAY,
      'timeOfDay' => 50400,// 2 p.m.
    ];
    $this->post_notification_scheduler->processPostNotificationSchedule($newsletter);
    $newsletter_option = NewsletterOption::where('newsletter_id', $newsletter->id)
      ->where('option_field_id', $newsletter_option_field->id)
      ->findOne();
    $current_time = 1485694800; // Sunday, 29 January 2017 @ 1:00pm (UTC)
    expect(Scheduler::getNextRunDate($newsletter_option->value, $current_time))
      ->equals('2017-02-25 14:00:00');
  }

  function testItProcessesPostNotificationScheduledForImmediateDelivery() {
    $newsletter_option_field = NewsletterOptionField::create();
    $newsletter_option_field->name = 'schedule';
    $newsletter_option_field->newsletter_type = Newsletter::TYPE_NOTIFICATION;
    $newsletter_option_field->save();

    // notification is scheduled immediately (next minute)
    $newsletter = (object)[
      'id' => 1,
      'intervalType' => Scheduler::INTERVAL_IMMEDIATELY,
      'monthDay' => null,
      'nthWeekDay' => null,
      'weekDay' => null,
      'timeOfDay' => null,
    ];
    $this->post_notification_scheduler->processPostNotificationSchedule($newsletter);
    $newsletter_option = NewsletterOption::where('newsletter_id', $newsletter->id)
      ->where('option_field_id', $newsletter_option_field->id)
      ->findOne();
    $current_time = 1483275600; // Sunday, 1 January 2017 @ 1:00pm (UTC)
    expect(Scheduler::getNextRunDate($newsletter_option->value, $current_time))
      ->equals('2017-01-01 13:01:00');
  }


  function testUnsearchablePostTypeDoesNotSchedulePostNotification() {
    $hook = ContainerWrapper::getInstance()->get(Hooks::class);

    $newsletter = $this->_createNewsletter();

    $this->_createNewsletterOptions(
      $newsletter->id,
      [
        'intervalType' => Scheduler::INTERVAL_IMMEDIATELY,
        'schedule' => '* * * * *',
      ]
    );

    $this->_removePostNotificationHooks();
    register_post_type('post', ['exclude_from_search' => true]);
    $hook->setupPostNotifications();

    $post_data = [
      'post_title' => 'title',
      'post_status' => 'publish',
    ];
    wp_insert_post($post_data);

    $queue = SendingQueue::findTaskByNewsletterId($newsletter->id)->findOne();
    expect($queue)->equals(false);

    $this->_removePostNotificationHooks();
    register_post_type('post', ['exclude_from_search' => false]);
    $hook->setupPostNotifications();

    wp_insert_post($post_data);

    $queue = SendingQueue::findTaskByNewsletterId($newsletter->id)->findOne();
    expect($queue)->notequals(false);
  }

  function testSchedulerWontRunIfUnsentNotificationHistoryExists() {
    $newsletter = $this->_createNewsletter();

    $this->_createNewsletterOptions(
      $newsletter->id,
      [
        'intervalType' => Scheduler::INTERVAL_IMMEDIATELY,
        'schedule' => '* * * * *',
      ]
    );

    $notification_history = Newsletter::create();
    $notification_history->type = Newsletter::TYPE_NOTIFICATION_HISTORY;
    $notification_history->status = Newsletter::STATUS_SENDING;
    $notification_history->parent_id = $newsletter->id;
    $notification_history->save();

    $sending_task = SendingTask::create();
    $sending_task->newsletter_id = $notification_history->id;
    $sending_task->status = SendingQueue::STATUS_SCHEDULED;
    $sending_task->save();

    $post_data = [
      'post_title' => 'title',
      'post_status' => 'publish',
    ];
    wp_insert_post($post_data);

    $queue = SendingQueue::findTaskByNewsletterId($newsletter->id)->findOne();
    expect($queue)->equals(false);
  }

  function _removePostNotificationHooks() {
    foreach (WPPosts::getTypes() as $post_type) {
      remove_filter(
        'publish_' . $post_type,
        [$this->post_notification_scheduler, 'transitionHook'],
        10
      );
    }
  }

  function _createNewsletter() {
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_NOTIFICATION;
    $newsletter->status = Newsletter::STATUS_ACTIVE;
    $newsletter->save();
    expect($newsletter->getErrors())->false();
    return $newsletter;
  }

  function _createNewsletterOptions($newsletter_id, $options) {
    foreach ($options as $option => $value) {
      $newsletter_option_field = NewsletterOptionField::where('name', $option)->findOne();
      if (!$newsletter_option_field) {
        $newsletter_option_field = NewsletterOptionField::create();
        $newsletter_option_field->name = $option;
        $newsletter_option_field->newsletter_type = Newsletter::TYPE_NOTIFICATION;
        $newsletter_option_field->save();
        expect($newsletter_option_field->getErrors())->false();
      }

      $newsletter_option = NewsletterOption::create();
      $newsletter_option->option_field_id = $newsletter_option_field->id;
      $newsletter_option->newsletter_id = $newsletter_id;
      $newsletter_option->value = $value;
      $newsletter_option->save();
      expect($newsletter_option->getErrors())->false();
    }
  }

  function _after() {
    Carbon::setTestNow();
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterPost::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
  }



}
