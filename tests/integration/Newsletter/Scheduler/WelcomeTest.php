<?php

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\NewsletterPost;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class WelcomeTest extends \MailPoetTest {

  /** @var WelcomeScheduler */
  private $welcome_scheduler;

  public function _before() {
    parent::_before();
    $this->welcome_scheduler = new WelcomeScheduler;
  }

  public function testItDoesNotCreateDuplicateWelcomeNotificationSendingTasks() {
    $newsletter = (object)[
      'id' => 1,
      'afterTimeNumber' => 2,
      'afterTimeType' => 'hours',
    ];
    $existing_subscriber = 678;
    $existing_queue = SendingTask::create();
    $existing_queue->newsletter_id = $newsletter->id;
    $existing_queue->setSubscribers([$existing_subscriber]);
    $existing_queue->save();

    // queue is not scheduled
    $this->welcome_scheduler->createWelcomeNotificationSendingTask($newsletter, $existing_subscriber);
    expect(SendingQueue::findMany())->count(1);

    // queue is not scheduled
    $this->welcome_scheduler->createWelcomeNotificationSendingTask($newsletter, 1);
    expect(SendingQueue::findMany())->count(2);
  }

  public function testItCreatesWelcomeNotificationSendingTaskScheduledToSendInHours() {
    $newsletter = (object)[
      'id' => 1,
      'afterTimeNumber' => 2,
    ];

    // queue is scheduled delivery in 2 hours
    $newsletter->afterTimeType = 'hours';
    $this->welcome_scheduler->createWelcomeNotificationSendingTask($newsletter, $subscriber_id = 1);
    $queue = SendingQueue::findTaskByNewsletterId(1)
      ->findOne();
    $current_time = Carbon::createFromTimestamp(current_time('timestamp'));
    Carbon::setTestNow($current_time); // mock carbon to return current time
    expect($queue->id)->greaterOrEquals(1);
    expect($queue->priority)->equals(SendingQueue::PRIORITY_HIGH);
    expect(Carbon::parse($queue->scheduled_at)->format('Y-m-d H:i'))
      ->equals($current_time->addHours(2)->format('Y-m-d H:i'));
  }

  public function testItCreatesWelcomeNotificationSendingTaskScheduledToSendInDays() {
    $newsletter = (object)[
      'id' => 1,
      'afterTimeNumber' => 2,
    ];

    // queue is scheduled for delivery in 2 days
    $newsletter->afterTimeType = 'days';
    $this->welcome_scheduler->createWelcomeNotificationSendingTask($newsletter, $subscriber_id = 1);
    $current_time = Carbon::createFromTimestamp(current_time('timestamp'));
    Carbon::setTestNow($current_time); // mock carbon to return current time
    $queue = SendingQueue::findTaskByNewsletterId(1)
      ->findOne();
    expect($queue->id)->greaterOrEquals(1);
    expect($queue->priority)->equals(SendingQueue::PRIORITY_HIGH);
    expect(Carbon::parse($queue->scheduled_at)->format('Y-m-d H:i'))
      ->equals($current_time->addDays(2)->format('Y-m-d H:i'));
  }

  public function testItCreatesWelcomeNotificationSendingTaskScheduledToSendInWeeks() {
    $newsletter = (object)[
      'id' => 1,
      'afterTimeNumber' => 2,
    ];

    // queue is scheduled for delivery in 2 weeks
    $newsletter->afterTimeType = 'weeks';
    $this->welcome_scheduler->createWelcomeNotificationSendingTask($newsletter, $subscriber_id = 1);
    $current_time = Carbon::createFromTimestamp(current_time('timestamp'));
    Carbon::setTestNow($current_time); // mock carbon to return current time
    $queue = SendingQueue::findTaskByNewsletterId(1)
      ->findOne();
    expect($queue->id)->greaterOrEquals(1);
    expect($queue->priority)->equals(SendingQueue::PRIORITY_HIGH);
    expect(Carbon::parse($queue->scheduled_at)->format('Y-m-d H:i'))
      ->equals($current_time->addWeeks(2)->format('Y-m-d H:i'));
  }

  public function testItCreatesWelcomeNotificationSendingTaskScheduledToSendImmediately() {
    $newsletter = (object)[
      'id' => 1,
      'afterTimeNumber' => 2,
    ];

    // queue is scheduled for immediate delivery
    $newsletter->afterTimeType = null;
    $this->welcome_scheduler->createWelcomeNotificationSendingTask($newsletter, $subscriber_id = 1);
    $current_time = Carbon::createFromTimestamp(current_time('timestamp'));
    Carbon::setTestNow($current_time); // mock carbon to return current time
    $queue = SendingQueue::findTaskByNewsletterId(1)->findOne();
    expect($queue->id)->greaterOrEquals(1);
    expect($queue->priority)->equals(SendingQueue::PRIORITY_HIGH);
    expect(Carbon::parse($queue->scheduled_at)->format('Y-m-d H:i'))
      ->equals($current_time->format('Y-m-d H:i'));
  }

  public function testItDoesNotSchedulesSubscriberWelcomeNotificationWhenSubscriberIsNotInSegment() {
    // do not schedule when subscriber is not in segment
    $newsletter = $this->_createNewsletter();
    $this->welcome_scheduler->scheduleSubscriberWelcomeNotification(
      $subscriber_id = 10,
      $segments = []
    );

    // queue is not created
    $queue = SendingQueue::findTaskByNewsletterId($newsletter->id)
      ->findOne();
    expect($queue)->false();
  }

  public function testItSchedulesSubscriberWelcomeNotification() {
    $newsletter = $this->_createNewsletter();
    $this->_createNewsletterOptions(
      $newsletter->id,
      [
        'event' => 'segment',
        'segment' => 2,
        'afterTimeType' => 'days',
        'afterTimeNumber' => 1,
      ]
    );

    // queue is created and scheduled for delivery one day later
    $result = $this->welcome_scheduler->scheduleSubscriberWelcomeNotification(
      $subscriber_id = 10,
      $segments = [
        3,
        2,
        1,
      ]
    );
    $current_time = Carbon::createFromTimestamp(current_time('timestamp'));
    Carbon::setTestNow($current_time); // mock carbon to return current time
    $queue = SendingQueue::findTaskByNewsletterId($newsletter->id)
      ->findOne();
    expect(Carbon::parse($queue->scheduled_at)->format('Y-m-d H:i'))
      ->equals($current_time->addDay()->format('Y-m-d H:i'));
    expect($result[0]->id())->equals($queue->id());
  }


  public function itDoesNotScheduleAnythingWhenNewsletterDoesNotExist() {

    // subscriber welcome notification is not scheduled
    $result = $this->welcome_scheduler->scheduleSubscriberWelcomeNotification(
      $subscriber_id = 10,
      $segments = []
    );
    expect($result)->false();

    // WP user welcome notification is not scheduled
    $result = $this->welcome_scheduler->scheduleSubscriberWelcomeNotification(
      $subscriber_id = 10,
      $segments = []
    );
    expect($result)->false();
  }

  public function testItDoesNotScheduleWPUserWelcomeNotificationWhenRoleHasNotChanged() {
    $newsletter = $this->_createNewsletter();
    $this->_createNewsletterOptions(
      $newsletter->id,
      [
        'event' => 'user',
        'role' => 'editor',
        'afterTimeType' => 'days',
        'afterTimeNumber' => 1,
      ]
    );
    $this->welcome_scheduler->scheduleWPUserWelcomeNotification(
      $subscriber_id = 10,
      $wp_user = ['roles' => ['editor']],
      $old_user_data = ['roles' => ['editor']]
    );

    // queue is not created
    $queue = SendingQueue::findTaskByNewsletterId($newsletter->id)
      ->findOne();
    expect($queue)->false();
  }

  public function testItDoesNotScheduleWPUserWelcomeNotificationWhenUserRoleDoesNotMatch() {
    $newsletter = $this->_createNewsletter();
    $this->_createNewsletterOptions(
      $newsletter->id,
      [
        'event' => 'user',
        'role' => 'editor',
        'afterTimeType' => 'days',
        'afterTimeNumber' => 1,
      ]
    );
    $this->welcome_scheduler->scheduleWPUserWelcomeNotification(
      $subscriber_id = 10,
      $wp_user = ['roles' => ['administrator']]
    );

    // queue is not created
    $queue = SendingQueue::findTaskByNewsletterId($newsletter->id)
      ->findOne();
    expect($queue)->false();
  }

  public function testItSchedulesWPUserWelcomeNotificationWhenUserRolesMatches() {
    $newsletter = $this->_createNewsletter();
    $this->_createNewsletterOptions(
      $newsletter->id,
      [
        'event' => 'user',
        'role' => 'administrator',
        'afterTimeType' => 'days',
        'afterTimeNumber' => 1,
      ]
    );
    $this->welcome_scheduler->scheduleWPUserWelcomeNotification(
      $subscriber_id = 10,
      $wp_user = ['roles' => ['administrator']]
    );
    $current_time = Carbon::createFromTimestamp(current_time('timestamp'));
    Carbon::setTestNow($current_time); // mock carbon to return current time
    // queue is created and scheduled for delivery one day later
    $queue = SendingQueue::findTaskByNewsletterId($newsletter->id)
      ->findOne();
    expect(Carbon::parse($queue->scheduled_at)->format('Y-m-d H:i'))
      ->equals($current_time->addDay()->format('Y-m-d H:i'));
  }

  public function testItSchedulesWPUserWelcomeNotificationWhenUserHasAnyRole() {
    $newsletter = $this->_createNewsletter();
    $this->_createNewsletterOptions(
      $newsletter->id,
      [
        'event' => 'user',
        'role' => WelcomeScheduler::WORDPRESS_ALL_ROLES,
        'afterTimeType' => 'days',
        'afterTimeNumber' => 1,
      ]
    );
    $this->welcome_scheduler->scheduleWPUserWelcomeNotification(
      $subscriber_id = 10,
      $wp_user = ['roles' => ['administrator']]
    );
    $current_time = Carbon::createFromTimestamp(current_time('timestamp'));
    Carbon::setTestNow($current_time); // mock carbon to return current time
    // queue is created and scheduled for delivery one day later
    $queue = SendingQueue::findTaskByNewsletterId($newsletter->id)
      ->findOne();
    expect(Carbon::parse($queue->scheduled_at)->format('Y-m-d H:i'))
      ->equals($current_time->addDay()->format('Y-m-d H:i'));
  }

  public function _createNewsletter(
    $status = Newsletter::STATUS_ACTIVE
  ) {
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_WELCOME;
    $newsletter->status = $status;
    $newsletter->save();
    expect($newsletter->getErrors())->false();
    return $newsletter;
  }

  public function _createNewsletterOptions($newsletter_id, $options) {
    foreach ($options as $option => $value) {
      $newsletter_option_field = NewsletterOptionField::where('name', $option)->findOne();
      if (!$newsletter_option_field) {
        $newsletter_option_field = NewsletterOptionField::create();
        $newsletter_option_field->name = $option;
        $newsletter_option_field->newsletter_type = Newsletter::TYPE_WELCOME;
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
