<?php
namespace MailPoet\Test\Newsletter\Scheduler;

use Carbon\Carbon;
use Codeception\Util\Fixtures;
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
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Posts as WPPosts;

class SchedulerTest extends \MailPoetTest {
  function testItSetsConstants() {
    expect(Scheduler::SECONDS_IN_HOUR)->notEmpty();
    expect(Scheduler::LAST_WEEKDAY_FORMAT)->notEmpty();
    expect(Scheduler::WORDPRESS_ALL_ROLES)->notEmpty();
    expect(Scheduler::INTERVAL_IMMEDIATELY)->notEmpty();
    expect(Scheduler::INTERVAL_IMMEDIATE)->notEmpty();
    expect(Scheduler::INTERVAL_DAILY)->notEmpty();
    expect(Scheduler::INTERVAL_WEEKLY)->notEmpty();
    expect(Scheduler::INTERVAL_MONTHLY)->notEmpty();
    expect(Scheduler::INTERVAL_NTHWEEKDAY)->notEmpty();
  }

  function testItGetsActiveNewslettersFilteredByTypeAndGroup() {
    $this->_createNewsletter($type = Newsletter::TYPE_WELCOME);

    // no newsletters with type "notification" should be found
    expect(Scheduler::getNewsletters(Newsletter::TYPE_NOTIFICATION))->isEmpty();

    // one newsletter with type "welcome" should be found
    expect(Scheduler::getNewsletters(Newsletter::TYPE_WELCOME))->count(1);

    // one automatic email belonging to "test" group should be found
    $newsletter = $this->_createNewsletter($type = Newsletter::TYPE_AUTOMATIC);
    $this->_createNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_AUTOMATIC,
      [
        'group' => 'test',
      ]
    );

    expect(Scheduler::getNewsletters(Newsletter::TYPE_AUTOMATIC, 'group_does_not_exist'))->isEmpty();
    expect(Scheduler::getNewsletters(Newsletter::TYPE_WELCOME, 'test'))->count(1);
  }

  function testItCanGetNextRunDate() {
    // it accepts cron syntax and returns next run date
    $current_time = Carbon::createFromTimestamp(current_time('timestamp'));
    Carbon::setTestNow($current_time); // mock carbon to return current time
    expect(Scheduler::getNextRunDate('* * * * *'))
      ->equals($current_time->addMinute()->format('Y-m-d H:i:00'));
    // when invalid CRON expression is used, false response is returned
    expect(Scheduler::getNextRunDate('invalid CRON expression'))->false();
  }

  function testItCanGetPreviousRunDate() {
    // it accepts cron syntax and returns previous run date
    $current_time = Carbon::createFromTimestamp(current_time('timestamp'));
    Carbon::setTestNow($current_time); // mock carbon to return current time
    expect(Scheduler::getPreviousRunDate('* * * * *'))
      ->equals($current_time->subMinute()->format('Y-m-d H:i:00'));
    // when invalid CRON expression is used, false response is returned
    expect(Scheduler::getPreviousRunDate('invalid CRON expression'))->false();
  }

  function testItFormatsDatetimeString() {
    expect(Scheduler::formatDatetimeString('April 20, 2016 4pm'))
      ->equals('2016-04-20 16:00:00');
  }

  function testItCreatesPostNotificationSendingTask() {
    $newsletter = $this->_createNewsletter();
    $newsletter->schedule = '* 5 * * *';

    // new queue record should be created
    $queue = Scheduler::createPostNotificationSendingTask($newsletter);
    expect(SendingQueue::findMany())->count(1);
    expect($queue->newsletter_id)->equals($newsletter->id);
    expect($queue->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect($queue->scheduled_at)->equals(Scheduler::getNextRunDate('* 5 * * *'));
    expect($queue->priority)->equals(SendingQueue::PRIORITY_MEDIUM);

    // duplicate queue record should not be created
    Scheduler::createPostNotificationSendingTask($newsletter);
    expect(SendingQueue::findMany())->count(1);
  }

  function testItCreatesPostNotificationSendingTaskIfAPausedNotificationExists() {
    $newsletter = $this->_createNewsletter();
    $newsletter->schedule = '* 5 * * *';

    // new queue record should be created
    $queue_to_be_paused = Scheduler::createPostNotificationSendingTask($newsletter);
    $queue_to_be_paused->task()->pause();

    // another queue record should be created because the first one was paused
    $newsletter->schedule = '* 10 * * *'; // different time to not clash with the first queue
    $queue = Scheduler::createPostNotificationSendingTask($newsletter);
    expect(SendingQueue::findMany())->count(2);
    expect($queue->newsletter_id)->equals($newsletter->id);
    expect($queue->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect($queue->scheduled_at)->equals(Scheduler::getNextRunDate('* 10 * * *'));
    expect($queue->priority)->equals(SendingQueue::PRIORITY_MEDIUM);

    // duplicate queue record should not be created
    Scheduler::createPostNotificationSendingTask($newsletter);
    expect(SendingQueue::findMany())->count(2);
  }

  function tesIttDoesNotSchedulePostNotificationWhenNotificationWasAlreadySentForPost() {
    $newsletter = $this->_createNewsletter();
    $newsletter_post = NewsletterPost::create();
    $newsletter_post->newsletter_id = $newsletter->id;
    $newsletter_post->post_id = 10;
    $newsletter_post->save();

    // queue is not created when notification was already sent for the post
    Scheduler::schedulePostNotification($post_id = 10);
    $queue = SendingQueue::findTaskByNewsletterId($newsletter->id)
      ->findOne();
    expect($queue)->false();
  }

  function testItSchedulesPostNotification() {
    $newsletter = $this->_createNewsletter();
    $this->_createNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_NOTIFICATION,
      [
        'schedule' => '0 5 * * *',
      ]
    );

    // queue is created and scheduled for delivery one day later at 5 a.m.
    Scheduler::schedulePostNotification($post_id = 10);
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
    Scheduler::processPostNotificationSchedule($newsletter);
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
    Scheduler::processPostNotificationSchedule($newsletter);
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
    Scheduler::processPostNotificationSchedule($newsletter);
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
    Scheduler::processPostNotificationSchedule($newsletter);
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
    Scheduler::processPostNotificationSchedule($newsletter);
    $newsletter_option = NewsletterOption::where('newsletter_id', $newsletter->id)
      ->where('option_field_id', $newsletter_option_field->id)
      ->findOne();
    $current_time = 1483275600; // Sunday, 1 January 2017 @ 1:00pm (UTC)
    expect(Scheduler::getNextRunDate($newsletter_option->value, $current_time))
      ->equals('2017-01-01 13:01:00');
  }

  function testItCreatesScheduledAutomaticEmailSendingTaskForUser() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_AUTOMATIC);
    $this->_createNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_AUTOMATIC,
      [
        'sendTo' => 'user',
        'afterTimeType' => 'hours',
        'afterTimeNumber' => 2,
      ]
    );
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_AUTOMATIC)->findOne($newsletter->id);
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();

    Scheduler::createAutomaticEmailSendingTask($newsletter, $subscriber->id, $meta = null);
    // new scheduled task should be created
    $task = SendingTask::getByNewsletterId($newsletter->id);
    $current_time = Carbon::createFromTimestamp(current_time('timestamp'));
    Carbon::setTestNow($current_time); // mock carbon to return current time
    expect($task->id)->greaterOrEquals(1);
    expect($task->priority)->equals(SendingQueue::PRIORITY_MEDIUM);
    expect($task->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect(Carbon::parse($task->scheduled_at)->format('Y-m-d H:i'))
      ->equals($current_time->addHours(2)->format('Y-m-d H:i'));
    // task should have 1 associated user
    $subscribers = $task->subscribers()->findMany();
    expect($subscribers)->count(1);
    expect($subscribers[0]->id)->equals($subscriber->id);
  }

  function testItAddsMetaToSendingQueueWhenCreatingAutomaticEmailSendingTask() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_AUTOMATIC);
    $this->_createNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_AUTOMATIC,
      [
        'sendTo' => 'user',
        'afterTimeType' => 'hours',
        'afterTimeNumber' => 2,
      ]
    );
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_AUTOMATIC)->findOne($newsletter->id);
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $meta = ['some' => 'value'];

    Scheduler::createAutomaticEmailSendingTask($newsletter, $subscriber->id, $meta);
    // new queue record should be created with meta data
    $queue = SendingQueue::where('newsletter_id', $newsletter->id)->findOne();
    expect($queue->getMeta())->equals($meta);
  }

  function testItCreatesAutomaticEmailSendingTaskForSegment() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_AUTOMATIC);
    $this->_createNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_AUTOMATIC,
      [
        'sendTo' => 'segment',
        'afterTimeType' => 'hours',
        'afterTimeNumber' => 2,
      ]
    );
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_AUTOMATIC)->findOne($newsletter->id);

    Scheduler::createAutomaticEmailSendingTask($newsletter, $subscriber = null, $meta = null);
    // new scheduled task should be created
    $task = SendingTask::getByNewsletterId($newsletter->id);
    $current_time = Carbon::createFromTimestamp(current_time('timestamp'));
    Carbon::setTestNow($current_time); // mock carbon to return current time
    expect($task->id)->greaterOrEquals(1);
    expect($task->priority)->equals(SendingQueue::PRIORITY_MEDIUM);
    expect($task->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect(Carbon::parse($task->scheduled_at)->format('Y-m-d H:i'))
      ->equals($current_time->addHours(2)->format('Y-m-d H:i'));
    // task should not have any subscribers
    $subscribers = $task->subscribers()->findMany();
    expect($subscribers)->count(0);
  }

  function testItDoesNotScheduleAutomaticEmailWhenGroupDoesNotMatch() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_AUTOMATIC);
    $this->_createNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_AUTOMATIC,
      [
        'group' => 'some_group',
        'event' => 'some_event',
        'sendTo' => 'user',
        'afterTimeType' => 'hours',
        'afterTimeNumber' => 2,
      ]
    );

    // email should not be scheduled when group is not matched
    Scheduler::scheduleAutomaticEmail('group_does_not_exist', 'some_event');
    expect(SendingQueue::findMany())->count(0);
  }

  function testItDoesNotScheduleAutomaticEmailWhenEventDoesNotMatch() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_AUTOMATIC);
    $this->_createNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_AUTOMATIC,
      [
        'group' => 'some_group',
        'event' => 'some_event',
        'sendTo' => 'user',
        'afterTimeType' => 'hours',
        'afterTimeNumber' => 2,
      ]
    );

    // email should not be scheduled when event is not matched
    Scheduler::scheduleAutomaticEmail('some_group', 'event_does_not_exist');
    expect(SendingQueue::findMany())->count(0);
  }

  function testItSchedulesAutomaticEmailWhenConditionMatches() {
    $current_time = Carbon::createFromTimestamp(current_time('timestamp'));
    Carbon::setTestNow($current_time); // mock carbon to return current time
    $newsletter_1 = $this->_createNewsletter(Newsletter::TYPE_AUTOMATIC);
    $this->_createNewsletterOptions(
      $newsletter_1->id,
      Newsletter::TYPE_AUTOMATIC,
      [
        'group' => 'some_group',
        'event' => 'some_event',
        'sendTo' => 'user',
        'afterTimeType' => 'hours',
        'afterTimeNumber' => 2,
      ]
    );
    $newsletter_2 = $this->_createNewsletter(Newsletter::TYPE_AUTOMATIC);
    $this->_createNewsletterOptions(
      $newsletter_2->id,
      Newsletter::TYPE_AUTOMATIC,
      [
        'group' => 'some_group',
        'event' => 'some_event',
        'sendTo' => 'segment',
        'afterTimeType' => 'hours',
        'afterTimeNumber' => 2,
      ]
    );
    $condition = function($email) {
      return $email->sendTo === 'segment';
    };

    // email should only be scheduled if it matches condition ("send to segment")
    Scheduler::scheduleAutomaticEmail('some_group', 'some_event', $condition);
    $result = SendingQueue::findMany();
    expect($result)->count(1);
    expect($result[0]->newsletter_id)->equals($newsletter_2->id);
    // scheduled task should be created
    $task = $result[0]->getTasks()->findOne();
    expect($task->id)->greaterOrEquals(1);
    expect($task->priority)->equals(SendingQueue::PRIORITY_MEDIUM);
    expect($task->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect(Carbon::parse($task->scheduled_at)->format('Y-m-d H:i'))
      ->equals($current_time->addHours(2)->format('Y-m-d H:i'));
  }

  function testUnsearchablePostTypeDoesNotSchedulePostNotification() {
    $hook = ContainerWrapper::getInstance()->get(Hooks::class);

    $newsletter = $this->_createNewsletter(Newsletter::TYPE_NOTIFICATION);

    $this->_createNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_NOTIFICATION,
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
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_NOTIFICATION);

    $this->_createNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_NOTIFICATION,
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

  function _createQueue(
    $newsletter_id,
    $scheduled_at = null,
    $status = SendingQueue::STATUS_SCHEDULED
  ) {
    $queue = SendingTask::create();
    $queue->status = $status;
    $queue->newsletter_id = $newsletter_id;
    $queue->scheduled_at = $scheduled_at;
    $queue->save();
    expect($queue->getErrors())->false();
    return $queue;
  }

  function _createNewsletter(
    $type = Newsletter::TYPE_NOTIFICATION,
    $status = Newsletter::STATUS_ACTIVE
  ) {
    $newsletter = Newsletter::create();
    $newsletter->type = $type;
    $newsletter->status = $status;
    $newsletter->save();
    expect($newsletter->getErrors())->false();
    return $newsletter;
  }

  function _createNewsletterOptions($newsletter_id, $newsletter_type, $options) {
    foreach ($options as $option => $value) {
      $newsletter_option_field = NewsletterOptionField::where('name', $option)->findOne();
      if (!$newsletter_option_field) {
        $newsletter_option_field = NewsletterOptionField::create();
        $newsletter_option_field->name = $option;
        $newsletter_option_field->newsletter_type = $newsletter_type;
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

  function _removePostNotificationHooks() {
    foreach (WPPosts::getTypes() as $post_type) {
      remove_filter(
        'publish_' . $post_type,
        '\MailPoet\Newsletter\Scheduler\Scheduler::transitionHook',
        10, 1
      );
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
