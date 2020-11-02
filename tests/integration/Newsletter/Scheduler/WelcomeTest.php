<?php

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\NewsletterPost;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class WelcomeTest extends \MailPoetTest {

  /** @var WelcomeScheduler */
  private $welcomeScheduler;

  /** @var SubscriberEntity */
  private $subscriber;

  /** @var SegmentEntity */
  private $segment;

  /** @var SegmentEntity */
  private $wpSegment;

  public function _before() {
    parent::_before();
    $this->welcomeScheduler = $this->diContainer->get(WelcomeScheduler::class);
    $this->subscriber = $this->createSubscriber('welcome_test_1@example.com');
    $this->segment = $this->createSegment('welcome_segment');
    $this->wpSegment = $this->createSegment('Wordpress', SegmentEntity::TYPE_WP_USERS);
  }

  public function testItDoesNotCreateDuplicateWelcomeNotificationSendingTasks() {
    $newsletter = (object)[
      'id' => 1,
      'afterTimeNumber' => 2,
      'afterTimeType' => 'hours',
      'event' => 'segment',
      'segment' => $this->segment->getId(),
    ];
    $existingSubscriber = $this->subscriber->getId();
    $existingQueue = SendingTask::create();
    $existingQueue->newsletterId = $newsletter->id;
    $existingQueue->setSubscribers([$existingSubscriber]);
    $existingQueue->save();

    // queue is not scheduled
    $this->welcomeScheduler->createWelcomeNotificationSendingTask($newsletter, $existingSubscriber);
    expect(SendingQueue::findMany())->count(1);

    // queue is scheduled
    $unscheduledSubscriber = $this->createSubscriber('welcome_test_2@example.com');
    $this->welcomeScheduler->createWelcomeNotificationSendingTask($newsletter, $unscheduledSubscriber->getId());
    expect(SendingQueue::findMany())->count(2);
  }

  public function testItCreatesWelcomeNotificationSendingTaskScheduledToSendInHours() {
    $newsletter = (object)[
      'id' => 1,
      'afterTimeNumber' => 2,
      'event' => 'segment',
      'segment' => $this->segment->getId(),
    ];

    // queue is scheduled delivery in 2 hours
    $newsletter->afterTimeType = 'hours';
    $this->welcomeScheduler->createWelcomeNotificationSendingTask($newsletter, $this->subscriber->getId());
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
      'event' => 'segment',
      'segment' => $this->segment->getId(),
    ];

    // queue is scheduled for delivery in 2 days
    $newsletter->afterTimeType = 'days';
    $this->welcomeScheduler->createWelcomeNotificationSendingTask($newsletter, $this->subscriber->getId());
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
      'event' => 'segment',
      'segment' => $this->segment->getId(),
    ];

    // queue is scheduled for delivery in 2 weeks
    $newsletter->afterTimeType = 'weeks';
    $this->welcomeScheduler->createWelcomeNotificationSendingTask($newsletter, $this->subscriber->getId());
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
      'event' => 'segment',
      'segment' => $this->segment->getId(),
    ];

    // queue is scheduled for immediate delivery
    $newsletter->afterTimeType = null;
    $this->welcomeScheduler->createWelcomeNotificationSendingTask($newsletter, $this->subscriber->getId());
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
      $this->subscriber->getId(),
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
        'segment' => $this->segment->getId(),
        'afterTimeType' => 'days',
        'afterTimeNumber' => 1,
      ]
    );

    $segment2 = $this->createSegment('Segment 2');
    $segment3 = $this->createSegment('Segment 3');

    // queue is created and scheduled for delivery one day later
    $result = $this->welcomeScheduler->scheduleSubscriberWelcomeNotification(
      $this->subscriber->getId(),
      $segments = [
        $this->segment->getId(),
        $segment2->getId(),
        $segment3->getId(),
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

  public function testItDoesNotScheduleWelcomeNotificationWhenSubscriberIsInTrash() {
    $newsletter = $this->_createNewsletter();
    $this->_createNewsletterOptions(
      $newsletter->id,
      [
        'event' => 'segment',
        'segment' => $this->segment->getId(),
        'afterTimeType' => 'days',
        'afterTimeNumber' => 1,
      ]
    );
    $trashedSubscriber = $this->createSubscriber('trashed@example.com');
    $trashedSubscriber->setDeletedAt(Carbon::now());
    $this->entityManager->flush();
    // subscriber welcome notification is not scheduled
    $result = $this->welcomeScheduler->scheduleSubscriberWelcomeNotification(
      $trashedSubscriber->getId(),
      $segments = [$this->segment->getId()]
    );
    expect($result)->false();
  }

  public function testItDoesNotScheduleWelcomeNotificationWhenSegmentIsInTrash() {
    $newsletter = $this->_createNewsletter();
    $this->_createNewsletterOptions(
      $newsletter->id,
      [
        'event' => 'segment',
        'segment' => $this->segment->getId(),
        'afterTimeType' => 'days',
        'afterTimeNumber' => 1,
      ]
    );
    $this->segment->setDeletedAt(Carbon::now());
    $this->entityManager->flush();
    // subscriber welcome notification is not scheduled
    $result = $this->welcomeScheduler->scheduleSubscriberWelcomeNotification(
      $this->subscriber->getId(),
      $segments = [$this->segment->getId()]
    );
    expect($result)->false();
  }

  public function itDoesNotScheduleAnythingWhenNewsletterDoesNotExist() {
    // subscriber welcome notification is not scheduled
    $result = $this->welcomeScheduler->scheduleSubscriberWelcomeNotification(
      $this->subscriber->getId(),
      $segments = []
    );
    expect($result)->false();

    // WP user welcome notification is not scheduled
    $result = $this->welcomeScheduler->scheduleWPUserWelcomeNotification(
      $this->subscriber->getId(),
      $wpUser = ['roles' => ['editor']]
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
      $subscriberId = $this->subscriber->getId(),
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
      $subscriberId = $this->subscriber->getId(),
      $wpUser = ['roles' => ['administrator']]
    );

    // queue is not created
    $queue = SendingQueue::findTaskByNewsletterId($newsletter->id)
      ->findOne();
    expect($queue)->false();
  }

  public function testItDoesNotSchedulesWPUserWelcomeNotificationWhenSubscriberIsInTrash() {
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
    $trashedSubscriber = $this->createSubscriber('trashed@example.com');
    $trashedSubscriber->setDeletedAt(Carbon::now());
    $this->entityManager->flush();
    $this->welcomeScheduler->scheduleWPUserWelcomeNotification(
      $subscriberId = $trashedSubscriber->getId(),
      $wpUser = ['roles' => ['administrator']]
    );
    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    Carbon::setTestNow($currentTime); // mock carbon to return current time
    // queue is created and scheduled for delivery one day later
    $queue = SendingQueue::findTaskByNewsletterId($newsletter->id)
      ->findOne();
    expect($queue)->false();
  }

  public function testItDoesNotSchedulesWPUserWelcomeNotificationWhenWpSegmentIsInTrash() {
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
    $this->wpSegment->setDeletedAt(Carbon::now());
    $this->entityManager->flush();
    $this->welcomeScheduler->scheduleWPUserWelcomeNotification(
      $subscriberId = $this->subscriber->getId(),
      $wpUser = ['roles' => ['administrator']]
    );
    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    Carbon::setTestNow($currentTime); // mock carbon to return current time
    // queue is created and scheduled for delivery one day later
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
      $subscriberId = $this->subscriber->getId(),
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
      $this->subscriber->getId(),
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

  private function createSubscriber($email): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriber->setEmail($email);
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    return $subscriber;
  }

  private function createSegment($name, $type = SegmentEntity::TYPE_DEFAULT): SegmentEntity {
    $segment = new SegmentEntity($name, $type, $name);
    $this->entityManager->persist($segment);
    $this->entityManager->flush();
    return $segment;
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
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
  }
}
