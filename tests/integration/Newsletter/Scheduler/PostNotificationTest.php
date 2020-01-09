<?php

namespace MailPoet\Newsletter\Scheduler;

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
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Posts as WPPosts;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class PostNotificationTest extends \MailPoetTest {

  /** @var PostNotificationScheduler */
  private $postNotificationScheduler;

  public function _before() {
    parent::_before();
    $this->postNotificationScheduler = new PostNotificationScheduler;
  }

  public function testItCreatesPostNotificationSendingTask() {
    $newsletter = $this->_createNewsletter();
    $newsletter->schedule = '* 5 * * *';

    // new queue record should be created
    $queue = $this->postNotificationScheduler->createPostNotificationSendingTask($newsletter);
    expect(SendingQueue::findMany())->count(1);
    expect($queue->newsletterId)->equals($newsletter->id);
    expect($queue->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect($queue->scheduledAt)->equals(Scheduler::getNextRunDate('* 5 * * *'));
    expect($queue->priority)->equals(SendingQueue::PRIORITY_MEDIUM);

    // duplicate queue record should not be created
    $this->postNotificationScheduler->createPostNotificationSendingTask($newsletter);
    expect(SendingQueue::findMany())->count(1);
  }

  public function testItCreatesPostNotificationSendingTaskIfAPausedNotificationExists() {
    $newsletter = $this->_createNewsletter();
    $newsletter->schedule = '* 5 * * *';

    // new queue record should be created
    $queueToBePaused = $this->postNotificationScheduler->createPostNotificationSendingTask($newsletter);
    $queueToBePaused->task()->pause();

    // another queue record should be created because the first one was paused
    $newsletter->schedule = '* 10 * * *'; // different time to not clash with the first queue
    $queue = $this->postNotificationScheduler->createPostNotificationSendingTask($newsletter);
    expect(SendingQueue::findMany())->count(2);
    expect($queue->newsletterId)->equals($newsletter->id);
    expect($queue->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect($queue->scheduledAt)->equals(Scheduler::getNextRunDate('* 10 * * *'));
    expect($queue->priority)->equals(SendingQueue::PRIORITY_MEDIUM);

    // duplicate queue record should not be created
    $this->postNotificationScheduler->createPostNotificationSendingTask($newsletter);
    expect(SendingQueue::findMany())->count(2);
  }

  public function tesIttDoesNotSchedulePostNotificationWhenNotificationWasAlreadySentForPost() {
    $newsletter = $this->_createNewsletter();
    $newsletterPost = NewsletterPost::create();
    $newsletterPost->newsletterId = $newsletter->id;
    $newsletterPost->postId = 10;
    $newsletterPost->save();

    // queue is not created when notification was already sent for the post
    $this->postNotificationScheduler->schedulePostNotification($postId = 10);
    $queue = SendingQueue::findTaskByNewsletterId($newsletter->id)
      ->findOne();
    expect($queue)->false();
  }

  public function testItSchedulesPostNotification() {
    $newsletter = $this->_createNewsletter();
    $this->_createNewsletterOptions(
      $newsletter->id,
      [
        'schedule' => '0 5 * * *',
      ]
    );

    // queue is created and scheduled for delivery one day later at 5 a.m.
    $this->postNotificationScheduler->schedulePostNotification($postId = 10);
    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    Carbon::setTestNow($currentTime); // mock carbon to return current time
    $nextRunDate = ($currentTime->hour < 5) ?
      $currentTime :
      $currentTime->addDay();
    $queue = SendingQueue::findTaskByNewsletterId($newsletter->id)
      ->findOne();
    expect($queue->scheduledAt)->startsWith($nextRunDate->format('Y-m-d 05:00'));
  }

  public function testItProcessesPostNotificationScheduledForDailyDelivery() {
    $newsletterOptionField = NewsletterOptionField::create();
    $newsletterOptionField->name = 'schedule';
    $newsletterOptionField->newsletterType = Newsletter::TYPE_NOTIFICATION;
    $newsletterOptionField->save();

    // daily notification is scheduled at 14:00
    $newsletter = (object)[
      'id' => 1,
      'intervalType' => PostNotificationScheduler::INTERVAL_DAILY,
      'monthDay' => null,
      'nthWeekDay' => null,
      'weekDay' => null,
      'timeOfDay' => 50400, // 2 p.m.
    ];
    $this->postNotificationScheduler->processPostNotificationSchedule($newsletter);
    $newsletterOption = NewsletterOption::where('newsletter_id', $newsletter->id)
      ->where('option_field_id', $newsletterOptionField->id)
      ->findOne();
    $currentTime = 1483275600; // Sunday, 1 January 2017 @ 1:00pm (UTC)
    expect(Scheduler::getNextRunDate($newsletterOption->value, $currentTime))
      ->equals('2017-01-01 14:00:00');
  }

  public function testItProcessesPostNotificationScheduledForWeeklyDelivery() {
    $newsletterOptionField = NewsletterOptionField::create();
    $newsletterOptionField->name = 'schedule';
    $newsletterOptionField->newsletterType = Newsletter::TYPE_NOTIFICATION;
    $newsletterOptionField->save();

    // weekly notification is scheduled every Tuesday at 14:00
    $newsletter = (object)[
      'id' => 1,
      'intervalType' => PostNotificationScheduler::INTERVAL_WEEKLY,
      'monthDay' => null,
      'nthWeekDay' => null,
      'weekDay' => Carbon::TUESDAY,
      'timeOfDay' => 50400, // 2 p.m.
    ];
    $this->postNotificationScheduler->processPostNotificationSchedule($newsletter);
    $newsletterOption = NewsletterOption::where('newsletter_id', $newsletter->id)
      ->where('option_field_id', $newsletterOptionField->id)
      ->findOne();
    $currentTime = 1483275600; // Sunday, 1 January 2017 @ 1:00pm (UTC)
    expect(Scheduler::getNextRunDate($newsletterOption->value, $currentTime))
      ->equals('2017-01-03 14:00:00');
  }

  public function testItProcessesPostNotificationScheduledForMonthlyDeliveryOnSpecificDay() {
    $newsletterOptionField = NewsletterOptionField::create();
    $newsletterOptionField->name = 'schedule';
    $newsletterOptionField->newsletterType = Newsletter::TYPE_NOTIFICATION;
    $newsletterOptionField->save();

    // monthly notification is scheduled every 20th day at 14:00
    $newsletter = (object)[
      'id' => 1,
      'intervalType' => PostNotificationScheduler::INTERVAL_MONTHLY,
      'monthDay' => 19, // 20th (count starts from 0)
      'nthWeekDay' => null,
      'weekDay' => null,
      'timeOfDay' => 50400,// 2 p.m.
    ];
    $this->postNotificationScheduler->processPostNotificationSchedule($newsletter);
    $newsletterOption = NewsletterOption::where('newsletter_id', $newsletter->id)
      ->where('option_field_id', $newsletterOptionField->id)
      ->findOne();
    $currentTime = 1483275600; // Sunday, 1 January 2017 @ 1:00pm (UTC)
    expect(Scheduler::getNextRunDate($newsletterOption->value, $currentTime))
      ->equals('2017-01-19 14:00:00');
  }

  public function testItProcessesPostNotificationScheduledForMonthlyDeliveryOnLastWeekDay() {
    $newsletterOptionField = NewsletterOptionField::create();
    $newsletterOptionField->name = 'schedule';
    $newsletterOptionField->newsletterType = Newsletter::TYPE_NOTIFICATION;
    $newsletterOptionField->save();

    // monthly notification is scheduled every last Saturday at 14:00
    $newsletter = (object)[
      'id' => 1,
      'intervalType' => PostNotificationScheduler::INTERVAL_NTHWEEKDAY,
      'monthDay' => null,
      'nthWeekDay' => 'L', // L = last
      'weekDay' => Carbon::SATURDAY,
      'timeOfDay' => 50400,// 2 p.m.
    ];
    $this->postNotificationScheduler->processPostNotificationSchedule($newsletter);
    $newsletterOption = NewsletterOption::where('newsletter_id', $newsletter->id)
      ->where('option_field_id', $newsletterOptionField->id)
      ->findOne();
    $currentTime = 1485694800; // Sunday, 29 January 2017 @ 1:00pm (UTC)
    expect(Scheduler::getNextRunDate($newsletterOption->value, $currentTime))
      ->equals('2017-02-25 14:00:00');
  }

  public function testItProcessesPostNotificationScheduledForImmediateDelivery() {
    $newsletterOptionField = NewsletterOptionField::create();
    $newsletterOptionField->name = 'schedule';
    $newsletterOptionField->newsletterType = Newsletter::TYPE_NOTIFICATION;
    $newsletterOptionField->save();

    // notification is scheduled immediately (next minute)
    $newsletter = (object)[
      'id' => 1,
      'intervalType' => PostNotificationScheduler::INTERVAL_IMMEDIATELY,
      'monthDay' => null,
      'nthWeekDay' => null,
      'weekDay' => null,
      'timeOfDay' => null,
    ];
    $this->postNotificationScheduler->processPostNotificationSchedule($newsletter);
    $newsletterOption = NewsletterOption::where('newsletter_id', $newsletter->id)
      ->where('option_field_id', $newsletterOptionField->id)
      ->findOne();
    $currentTime = 1483275600; // Sunday, 1 January 2017 @ 1:00pm (UTC)
    expect(Scheduler::getNextRunDate($newsletterOption->value, $currentTime))
      ->equals('2017-01-01 13:01:00');
  }


  public function testUnsearchablePostTypeDoesNotSchedulePostNotification() {
    $hook = ContainerWrapper::getInstance()->get(Hooks::class);

    $newsletter = $this->_createNewsletter();

    $this->_createNewsletterOptions(
      $newsletter->id,
      [
        'intervalType' => PostNotificationScheduler::INTERVAL_IMMEDIATELY,
        'schedule' => '* * * * *',
      ]
    );

    $this->_removePostNotificationHooks();
    register_post_type('post', ['exclude_from_search' => true]);
    $hook->setupPostNotifications();

    $postData = [
      'post_title' => 'title',
      'post_status' => 'publish',
    ];
    wp_insert_post($postData);

    $queue = SendingQueue::findTaskByNewsletterId($newsletter->id)->findOne();
    expect($queue)->equals(false);

    $this->_removePostNotificationHooks();
    register_post_type('post', ['exclude_from_search' => false]);
    $hook->setupPostNotifications();

    wp_insert_post($postData);

    $queue = SendingQueue::findTaskByNewsletterId($newsletter->id)->findOne();
    expect($queue)->notequals(false);
  }

  public function testSchedulerWontRunIfUnsentNotificationHistoryExists() {
    $newsletter = $this->_createNewsletter();

    $this->_createNewsletterOptions(
      $newsletter->id,
      [
        'intervalType' => PostNotificationScheduler::INTERVAL_IMMEDIATELY,
        'schedule' => '* * * * *',
      ]
    );

    $notificationHistory = Newsletter::create();
    $notificationHistory->type = Newsletter::TYPE_NOTIFICATION_HISTORY;
    $notificationHistory->status = Newsletter::STATUS_SENDING;
    $notificationHistory->parentId = $newsletter->id;
    $notificationHistory->save();

    $sendingTask = SendingTask::create();
    $sendingTask->newsletterId = $notificationHistory->id;
    $sendingTask->status = SendingQueue::STATUS_SCHEDULED;
    $sendingTask->save();

    $postData = [
      'post_title' => 'title',
      'post_status' => 'publish',
    ];
    wp_insert_post($postData);

    $queue = SendingQueue::findTaskByNewsletterId($newsletter->id)->findOne();
    expect($queue)->equals(false);
  }

  public function _removePostNotificationHooks() {
    foreach (WPPosts::getTypes() as $postType) {
      remove_filter(
        'publish_' . $postType,
        [$this->postNotificationScheduler, 'transitionHook'],
        10
      );
    }
  }

  public function _createNewsletter() {
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_NOTIFICATION;
    $newsletter->status = Newsletter::STATUS_ACTIVE;
    $newsletter->save();
    expect($newsletter->getErrors())->false();
    return $newsletter;
  }

  public function _createNewsletterOptions($newsletterId, $options) {
    foreach ($options as $option => $value) {
      $newsletterOptionField = NewsletterOptionField::where('name', $option)->findOne();
      if (!$newsletterOptionField) {
        $newsletterOptionField = NewsletterOptionField::create();
        $newsletterOptionField->name = $option;
        $newsletterOptionField->newsletterType = Newsletter::TYPE_NOTIFICATION;
        $newsletterOptionField->save();
        expect($newsletterOptionField->getErrors())->false();
      }

      $newsletterOption = NewsletterOption::create();
      $newsletterOption->optionFieldId = (int)$newsletterOptionField->id;
      $newsletterOption->newsletterId = $newsletterId;
      $newsletterOption->value = $value;
      $newsletterOption->save();
      expect($newsletterOption->getErrors())->false();
    }
  }

  public function _after() {
    Carbon::setTestNow();
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterPost::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
  }



}
