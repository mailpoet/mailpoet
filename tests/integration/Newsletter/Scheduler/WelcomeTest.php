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
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class WelcomeTest extends \MailPoetTest {

  /** @var WelcomeScheduler */
  private $welcome_scheduler;

  public function _before() {
    parent::_before();
    $this->welcomeScheduler = new WelcomeScheduler;
  }

  public function testItDoesNotCreateDuplicateWelcomeNotificationSendingTasks() {
    $newsletter = (object)[
      'id' => 1,
      'afterTimeNumber' => 2,
      'afterTimeType' => 'hours',
    ];
    $existingSubscriber = 678;
    $existingQueue = SendingTask::create();
    $existingQueue->newsletterId = $newsletter->id;
    $existingQueue->setSubscribers([$existingSubscriber]);
    $existingQueue->save();

    // queue is not scheduled
    $this->welcomeScheduler->createWelcomeNotificationSendingTask($newsletter, $existingSubscriber);
    expect(SendingQueue::findMany())->count(1);

    // queue is not scheduled
    $this->welcomeScheduler->createWelcomeNotificationSendingTask($newsletter, 1);
    expect(SendingQueue::findMany())->count(2);
  }

  public function testItCreatesWelcomeNotificationSendingTaskScheduledToSendInHours() {
    $newsletter = (object)[
      'id' => 1,
      'afterTimeNumber' => 2,
    ];

    // queue is scheduled delivery in 2 hours
    $newsletter->afterTimeType = 'hours';
    $this->welcomeScheduler->createWelcomeNotificationSendingTask($newsletter, $subscriberId = 1);
    $queue = SendingQueue::findTaskByNewsletterId(1)
      ->findOne();
    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    Carbon::setTestNow($currentTime); // mock carbon to return current time
    expect($queue->id)->greaterOrEquals(1);
    expect($queue->priority)->equals(SendingQueue::PRIORITY_HIGH);
    expect(Carbon::parse($queue->scheduledAt)->format('Y-m-d H:i'))
      ->equals($currentTime->addHours(2)->format('Y-m-d H:i'));
  }

  public function testItCreatesWelcomeNotificationSendingTaskScheduledToSendInDays() {
    $newsletter = (object)[
      'id' => 1,
      'afterTimeNumber' => 2,
    ];

    // queue is scheduled for delivery in 2 days
    $newsletter->afterTimeType = 'days';
    $this->welcomeScheduler->createWelcomeNotificationSendingTask($newsletter, $subscriberId = 1);
    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    Carbon::setTestNow($currentTime); // mock carbon to return current time
    $queue = SendingQueue::findTaskByNewsletterId(1)
      ->findOne();
    expect($queue->id)->greaterOrEquals(1);
    expect($queue->priority)->equals(SendingQueue::PRIORITY_HIGH);
    expect(Carbon::parse($queue->scheduledAt)->format('Y-m-d H:i'))
      ->equals($currentTime->addDays(2)->format('Y-m-d H:i'));
  }

  public function testItCreatesWelcomeNotificationSendingTaskScheduledToSendInWeeks() {
    $newsletter = (object)[
      'id' => 1,
      'afterTimeNumber' => 2,
    ];

    // queue is scheduled for delivery in 2 weeks
    $newsletter->afterTimeType = 'weeks';
    $this->welcomeScheduler->createWelcomeNotificationSendingTask($newsletter, $subscriberId = 1);
    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    Carbon::setTestNow($currentTime); // mock carbon to return current time
    $queue = SendingQueue::findTaskByNewsletterId(1)
      ->findOne();
    expect($queue->id)->greaterOrEquals(1);
    expect($queue->priority)->equals(SendingQueue::PRIORITY_HIGH);
    expect(Carbon::parse($queue->scheduledAt)->format('Y-m-d H:i'))
      ->equals($currentTime->addWeeks(2)->format('Y-m-d H:i'));
  }

  public function testItCreatesWelcomeNotificationSendingTaskScheduledToSendImmediately() {
    $newsletter = (object)[
      'id' => 1,
      'afterTimeNumber' => 2,
    ];

    // queue is scheduled for immediate delivery
    $newsletter->afterTimeType = null;
    $this->welcomeScheduler->createWelcomeNotificationSendingTask($newsletter, $subscriberId = 1);
    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    Carbon::setTestNow($currentTime); // mock carbon to return current time
    $queue = SendingQueue::findTaskByNewsletterId(1)->findOne();
    expect($queue->id)->greaterOrEquals(1);
    expect($queue->priority)->equals(SendingQueue::PRIORITY_HIGH);
    expect(Carbon::parse($queue->scheduledAt)->format('Y-m-d H:i'))
      ->equals($currentTime->format('Y-m-d H:i'));
  }

  public function testItDoesNotSchedulesSubscriberWelcomeNotificationWhenSubscriberIsNotInSegment() {
    // do not schedule when subscriber is not in segment
    $newsletter = $this->_createNewsletter();
    $this->welcomeScheduler->scheduleSubscriberWelcomeNotification(
      $subscriberId = 10,
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
    $result = $this->welcomeScheduler->scheduleSubscriberWelcomeNotification(
      $subscriberId = 10,
      $segments = [
        3,
        2,
        1,
      ]
    );
    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    Carbon::setTestNow($currentTime); // mock carbon to return current time
    $queue = SendingQueue::findTaskByNewsletterId($newsletter->id)
      ->findOne();
    expect(Carbon::parse($queue->scheduledAt)->format('Y-m-d H:i'))
      ->equals($currentTime->addDay()->format('Y-m-d H:i'));
    expect($result[0]->id())->equals($queue->id());
  }


  public function itDoesNotScheduleAnythingWhenNewsletterDoesNotExist() {

    // subscriber welcome notification is not scheduled
    $result = $this->welcomeScheduler->scheduleSubscriberWelcomeNotification(
      $subscriberId = 10,
      $segments = []
    );
    expect($result)->false();

    // WP user welcome notification is not scheduled
    $result = $this->welcomeScheduler->scheduleSubscriberWelcomeNotification(
      $subscriberId = 10,
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
    $this->welcomeScheduler->scheduleWPUserWelcomeNotification(
      $subscriberId = 10,
      $wpUser = ['roles' => ['editor']],
      $oldUserData = ['roles' => ['editor']]
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
    $this->welcomeScheduler->scheduleWPUserWelcomeNotification(
      $subscriberId = 10,
      $wpUser = ['roles' => ['administrator']]
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
    $this->welcomeScheduler->scheduleWPUserWelcomeNotification(
      $subscriberId = 10,
      $wpUser = ['roles' => ['administrator']]
    );
    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    Carbon::setTestNow($currentTime); // mock carbon to return current time
    // queue is created and scheduled for delivery one day later
    $queue = SendingQueue::findTaskByNewsletterId($newsletter->id)
      ->findOne();
    expect(Carbon::parse($queue->scheduledAt)->format('Y-m-d H:i'))
      ->equals($currentTime->addDay()->format('Y-m-d H:i'));
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
    $this->welcomeScheduler->scheduleWPUserWelcomeNotification(
      $subscriberId = 10,
      $wpUser = ['roles' => ['administrator']]
    );
    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    Carbon::setTestNow($currentTime); // mock carbon to return current time
    // queue is created and scheduled for delivery one day later
    $queue = SendingQueue::findTaskByNewsletterId($newsletter->id)
      ->findOne();
    expect(Carbon::parse($queue->scheduledAt)->format('Y-m-d H:i'))
      ->equals($currentTime->addDay()->format('Y-m-d H:i'));
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

  public function _createNewsletterOptions($newsletterId, $options) {
    foreach ($options as $option => $value) {
      $newsletterOptionField = NewsletterOptionField::where('name', $option)->findOne();
      if (!$newsletterOptionField) {
        $newsletterOptionField = NewsletterOptionField::create();
        $newsletterOptionField->name = $option;
        $newsletterOptionField->newsletterType = Newsletter::TYPE_WELCOME;
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
