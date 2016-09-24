<?php

use Carbon\Carbon;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\NewsletterPost;
use MailPoet\Models\SendingQueue;
use MailPoet\Newsletter\Scheduler\Scheduler;

class NewsletterSchedulerTest extends MailPoetTest {
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

  function _before() {
    $this->time = Carbon::createFromTimestamp(current_time('timestamp'));
  }

  function testItGetsActiveNewslettersFilteredByType() {
    $newsletter = $this->_createNewsletter($type = Newsletter::TYPE_WELCOME);

    // no newsletters wtih type "notification" should be found
    expect(empty(Scheduler::getNewsletters(Newsletter::TYPE_NOTIFICATION)))->true();

    // one newsletter with type "welcome" should be found
    expect(count(Scheduler::getNewsletters(Newsletter::TYPE_WELCOME)))->equals(1);
  }

  function testItCanGetNextRunDate() {
    // it accepts cron syntax and returns next run date
    expect(Scheduler::getNextRunDate('* * * * *'))
      ->equals($this->time->copy()->addMinute()->format('Y-m-d H:i:00'));
  }

  function testItFormatsDatetimeString() {
    expect(Scheduler::formatDatetimeString('April 20, 2016 4pm'))
      ->equals('2016-04-20 16:00:00');
  }

  function testItCreatesPostNotificationQueueRecord() {
    $newsletter = $this->_createNewsletter();
    $newsletter->schedule = '* 5 * * *';

    // new queue record should be created
    $queue = Scheduler::createPostNotificationQueue($newsletter);
    expect(count(SendingQueue::findMany()))->equals(1);
    expect($queue->newsletter_id)->equals($newsletter->id);
    expect($queue->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect($queue->scheduled_at)->equals(Scheduler::getNextRunDate('* 5 * * *'));

    // duplicate queue record should not be created
    Scheduler::createPostNotificationQueue($newsletter);
    expect(count(SendingQueue::findMany()))->equals(1);
  }

  function testItCreatesWelcomeNotificationQueueRecord() {
    $newsletter = (object)array(
      'id' => 1,
      'afterTimeNumber' => 2
    );

    // queue is scheduled delivery in 2 hours
    $newsletter->afterTimeType = 'hours';
    Scheduler::createWelcomeNotificationQueue($newsletter, $subscriber_id = 1);
    $queue = SendingQueue::where('newsletter_id', 1)
      ->findOne();
    expect($queue->id)->greaterOrEquals(1);
    expect(Carbon::parse($queue->scheduled_at)->format('Y-m-d H:i'))
      ->equals($this->time->copy()->addHours(2)->format('Y-m-d H:i'));
    $this->_after();

    // queue is scheduled for delivery in 2 days
    $newsletter->afterTimeType = 'days';
    Scheduler::createWelcomeNotificationQueue($newsletter, $subscriber_id = 1);
    $queue = SendingQueue::where('newsletter_id', 1)
      ->findOne();
    expect($queue->id)->greaterOrEquals(1);
    expect(Carbon::parse($queue->scheduled_at)->format('Y-m-d H:i'))
      ->equals($this->time->copy()->addDays(2)->format('Y-m-d H:i'));
    $this->_after();

    // queue is scheduled for delivery in 2 weeks
    $newsletter->afterTimeType = 'weeks';
    Scheduler::createWelcomeNotificationQueue($newsletter, $subscriber_id = 1);
    $queue = SendingQueue::where('newsletter_id', 1)
      ->findOne();
    expect($queue->id)->greaterOrEquals(1);
    expect(Carbon::parse($queue->scheduled_at)->format('Y-m-d H:i'))
      ->equals($this->time->copy()->addWeeks(2)->format('Y-m-d H:i'));
    $this->_after();

    // queue is scheduled for immediate delivery
    $newsletter->afterTimeType = null;
    Scheduler::createWelcomeNotificationQueue($newsletter, $subscriber_id = 1);
    $queue = SendingQueue::where('newsletter_id', 1)
      ->findOne();
    expect($queue->id)->greaterOrEquals(1);
    expect(Carbon::parse($queue->scheduled_at)->format('Y-m-d H:i'))
      ->equals($this->time->copy()->format('Y-m-d H:i'));
  }

  function tesIttDoesNotSchedulePostNotificationWhenNotificationWasAlreadySentForPost() {
    $newsletter = $this->_createNewsletter();
    $newsletter_post = NewsletterPost::create();
    $newsletter_post->newsletter_id = $newsletter->id;
    $newsletter_post->post_id = 10;
    $newsletter_post->save();

    // queue is not created when notification was already sent for the post
    Scheduler::schedulePostNotification($post_id = 10);
    $queue = SendingQueue::where('newsletter_id', $newsletter->id)
      ->findOne();
    expect($queue)->false();
  }

 function testItSchedulesPostNotification() {
    $newsletter = $this->_createNewsletter();
    $this->_createNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_NOTIFICATION,
      array(
        'schedule' => '* 5 * * *'
      )
    );

    // queue is created and scheduled for delivery one day later
    Scheduler::schedulePostNotification($post_id = 10);
    $queue = SendingQueue::where('newsletter_id', $newsletter->id)
      ->findOne();
    expect($queue->scheduled_at)->contains('05:00:00');
  }

  function testItDoesNotSchedulesSubscriberWelcomeNotificationWhenSubscriberIsNotInSegment() {
    // do not schedule when subscriber is not in segment
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_WELCOME);
    Scheduler::scheduleSubscriberWelcomeNotification(
      $subscriber_id = 10,
      $segments = array()
    );

    // queue is not created
    $queue = SendingQueue::where('newsletter_id', $newsletter->id)
      ->findOne();
    expect($queue)->false();
  }

  function testItSchedulesSubscriberWelcomeNotification() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_WELCOME);
    $this->_createNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_WELCOME,
      array(
        'event' => 'segment',
        'segment' => 2,
        'afterTimeType' => 'days',
        'afterTimeNumber' => 1
      )
    );

    // queue is created and scheduled for delivery one day later
    Scheduler::scheduleSubscriberWelcomeNotification(
      $subscriber_id = 10,
      $segments = array(
        3,
        2,
        1
      )
    );
    $queue = SendingQueue::where('newsletter_id', $newsletter->id)
      ->findOne();
    expect(Carbon::parse($queue->scheduled_at)->format('Y-m-d H:i'))
      ->equals($this->time->copy()->addDay()->format('Y-m-d H:i'));
  }

  function itDoesNotScheduleAnythingWhenNewsletterDoesNotExist() {
    // post notification is not scheduled
    expect(Scheduler::schedulePostNotification($post_id = 10))->false();

    // subscriber welcome notification is not scheduled
    $result = Scheduler::scheduleSubscriberWelcomeNotification(
      $subscriber_id = 10,
      $segments = array()
    );
    expect($result)->false();

    // WP user welcome notification is not scheduled
    $result = Scheduler::scheduleSubscriberWelcomeNotification(
      $subscriber_id = 10,
      $segments = array()
    );
    expect($result)->false();
  }

  function testItDoesNotScheduleWPUserWelcomeNotificationWhenRoleHasNotChanged() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_WELCOME);
    $this->_createNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_WELCOME,
      array(
        'event' => 'user',
        'role' => 'editor',
        'afterTimeType' => 'days',
        'afterTimeNumber' => 1
      )
    );
    Scheduler::scheduleWPUserWelcomeNotification(
      $subscriber_id = 10,
      $wp_user = (object)array('roles' => array('editor')),
      $old_user_data = (object)array('roles' => array('editor'))
    );

    // queue is not created
    $queue = SendingQueue::where('newsletter_id', $newsletter->id)
      ->findOne();
    expect($queue)->false();
  }

  function testItDoesNotScheduleWPUserWelcomeNotificationWhenUserRoleDoesNotMatch() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_WELCOME);
    $this->_createNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_WELCOME,
      array(
        'event' => 'user',
        'role' => 'editor',
        'afterTimeType' => 'days',
        'afterTimeNumber' => 1
      )
    );
    Scheduler::scheduleWPUserWelcomeNotification(
      $subscriber_id = 10,
      $wp_user = (object)array('roles' => array('administrator'))
    );

    // queue is not created
    $queue = SendingQueue::where('newsletter_id', $newsletter->id)
      ->findOne();
    expect($queue)->false();
  }

  function testItSchedulesWPUserWelcomeNotificationWhenUserRolesMatches() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_WELCOME);
    $this->_createNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_WELCOME,
      array(
        'event' => 'user',
        'role' => 'administrator',
        'afterTimeType' => 'days',
        'afterTimeNumber' => 1
      )
    );
    Scheduler::scheduleWPUserWelcomeNotification(
      $subscriber_id = 10,
      $wp_user = (object)array('roles' => array('administrator'))
    );

    // queue is created and scheduled for delivery one day later
    $queue = SendingQueue::where('newsletter_id', $newsletter->id)
      ->findOne();
    expect(Carbon::parse($queue->scheduled_at)->format('Y-m-d H:i'))
      ->equals($this->time->copy()->addDay()->format('Y-m-d H:i'));
  }

  function testItSchedulesWPUserWelcomeNotificationWhenUserHasAnyRole() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_WELCOME);
    $this->_createNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_WELCOME,
      array(
        'event' => 'user',
        'role' => Scheduler::WORDPRESS_ALL_ROLES,
        'afterTimeType' => 'days',
        'afterTimeNumber' => 1
      )
    );
    Scheduler::scheduleWPUserWelcomeNotification(
      $subscriber_id = 10,
      $wp_user = (object)array('roles' => array('administrator'))
    );

    // queue is created and scheduled for delivery one day later
    $queue = SendingQueue::where('newsletter_id', $newsletter->id)
      ->findOne();
    expect(Carbon::parse($queue->scheduled_at)->format('Y-m-d H:i'))
      ->equals($this->time->copy()->addDay()->format('Y-m-d H:i'));
  }

  function testItProcessesPostNotificationSchedule() {
    $newsletter_option_field = NewsletterOptionField::create();
    $newsletter_option_field->name = 'schedule';
    $newsletter_option_field->newsletter_type = Newsletter::TYPE_WELCOME;
    $newsletter_option_field->save();

    // daily notification is scheduled at 14:00
    $newletter = (object)array(
      'id' => 1,
      'intervalType' => Scheduler::INTERVAL_DAILY,
      'monthDay' => null,
      'nthWeekDay' => null,
      'weekDay' => null,
      'timeOfDay' => 50400 // 14:00
    );
    Scheduler::processPostNotificationSchedule($newletter);
    $newletter_option = NewsletterOption::where('newsletter_id', $newletter->id)
      ->where('option_field_id', $newsletter_option_field->id)
      ->findOne();
    expect(Scheduler::getNextRunDate($newletter_option->value))
      ->contains('14:00:00');

    // weekly notification is scheduled every Tuesday at 14:00
    $newletter = (object)array(
      'id' => 1,
      'intervalType' => Scheduler::INTERVAL_WEEKLY,
      'monthDay' => null,
      'nthWeekDay' => null,
      'weekDay' => 2, // Tuesday
      'timeOfDay' => 50400 // 14:00
    );
    Scheduler::processPostNotificationSchedule($newletter);
    $newletter_option = NewsletterOption::where('newsletter_id', $newletter->id)
      ->where('option_field_id', $newsletter_option_field->id)
      ->findOne();
    expect(Scheduler::getNextRunDate($newletter_option->value))
      ->equals($this->time->copy()->next(2)->format('Y-m-d 14:00:00'));

    // monthly notification is scheduled every 20th day at 14:00
    $newletter = (object)array(
      'id' => 1,
      'intervalType' => Scheduler::INTERVAL_MONTHLY,
      'monthDay' => 19, // 20th (count starts from 0)
      'nthWeekDay' => null,
      'weekDay' => null, // Tuesday
      'timeOfDay' => 50400 // 14:00
    );
    Scheduler::processPostNotificationSchedule($newletter);
    $newletter_option = NewsletterOption::where('newsletter_id', $newletter->id)
      ->where('option_field_id', $newsletter_option_field->id)
      ->findOne();
    expect(Scheduler::getNextRunDate($newletter_option->value))
      ->contains('-19 14:00:00');

    // monthly notification is scheduled every last Saturday at 14:00
    $newletter = (object)array(
      'id' => 1,
      'intervalType' => Scheduler::INTERVAL_NTHWEEKDAY,
      'monthDay' => null,
      'nthWeekDay' => 'L', // L = last
      'weekDay' => 6, // Saturday
      'timeOfDay' => 50400 // 14:00
    );
    Scheduler::processPostNotificationSchedule($newletter);
    $newletter_option = NewsletterOption::where('newsletter_id', $newletter->id)
      ->where('option_field_id', $newsletter_option_field->id)
      ->findOne();
    expect(Scheduler::getNextRunDate($newletter_option->value))
      ->equals($this->time->copy()->lastOfMonth(6)->format('Y-m-d 14:00:00'));

    // notification is scheduled immediately (next minute)
    $newletter = (object)array(
      'id' => 1,
      'intervalType' => Scheduler::INTERVAL_IMMEDIATELY,
      'monthDay' => null,
      'nthWeekDay' => null,
      'weekDay' => null,
      'timeOfDay' => null
    );
    Scheduler::processPostNotificationSchedule($newletter);
    $newletter_option = NewsletterOption::where('newsletter_id', $newletter->id)
      ->where('option_field_id', $newsletter_option_field->id)
      ->findOne();
    expect(Scheduler::getNextRunDate($newletter_option->value))
      ->equals($this->time->addMinute()->format('Y-m-d H:i:00'));
  }

  function _createQueue(
    $newsletter_id,
    $scheduled_at = null,
    $status = SendingQueue::STATUS_SCHEDULED
  ) {
    $queue = SendingQueue::create();
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
    foreach($options as $option => $value) {
      $newsletter_option_field = NewsletterOptionField::create();
      $newsletter_option_field->name = $option;
      $newsletter_option_field->newsletter_type = $newsletter_type;
      $newsletter_option_field->save();
      expect($newsletter_option_field->getErrors())->false();

      $newsletter_option = NewsletterOption::create();
      $newsletter_option->option_field_id = $newsletter_option_field->id;
      $newsletter_option->newsletter_id = $newsletter_id;
      $newsletter_option->value = $value;
      $newsletter_option->save();
      expect($newsletter_option->getErrors())->false();
    }
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterPost::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}