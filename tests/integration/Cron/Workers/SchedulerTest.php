<?php

namespace MailPoet\Test\Cron\Workers;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\Scheduler;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Segments\SubscribersFinder;
use MailPoet\Settings\SettingsRepository;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class SchedulerTest extends \MailPoetTest {
  public $cronHelper;
  /** @var LoggerFactory */
  private $loggerFactory;

  /** @var SubscribersFinder */
  private $subscribersFinder;

  public function _before() {
    parent::_before();
    $this->loggerFactory = LoggerFactory::getInstance();
    $this->cronHelper = $this->diContainer->get(CronHelper::class);
    $this->subscribersFinder = $this->diContainer->get(SubscribersFinder::class);
  }

  public function testItThrowsExceptionWhenExecutionLimitIsReached() {
    try {
      $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->loggerFactory, $this->cronHelper);
      $scheduler->process(microtime(true) - $this->cronHelper->getDaemonExecutionLimit());
      self::fail('Maximum execution time limit exception was not thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Maximum execution time has been reached.');
    }
  }

  public function testItCanGetScheduledQueues() {
    expect(Scheduler::getScheduledQueues())->isEmpty();
    $queue = SendingTask::create();
    $queue->newsletterId = 1;
    $queue->status = SendingQueue::STATUS_SCHEDULED;
    $queue->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();
    expect(Scheduler::getScheduledQueues())->notEmpty();
  }

  public function testItCanCreateNotificationHistory() {
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_NOTIFICATION;
    $newsletter->save();

    // ensure that notification history does not exist
    $notificationHistory = Newsletter::where('type', Newsletter::TYPE_NOTIFICATION_HISTORY)
      ->where('parent_id', $newsletter->id)
      ->findOne();
    expect($notificationHistory)->isEmpty();

    // create notification history and ensure that it exists
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->loggerFactory, $this->cronHelper);
    $scheduler->createNotificationHistory($newsletter->id);
    $notificationHistory = Newsletter::where('type', Newsletter::TYPE_NOTIFICATION_HISTORY)
      ->where('parent_id', $newsletter->id)
      ->findOne();
    expect($notificationHistory)->notEmpty();
  }

  public function testItCanDeleteQueueWhenDeliveryIsSetToImmediately() {
    $newsletter = $this->_createNewsletter();
    $newsletterOptionField =
      $this->_createNewsletterOptionField('intervalType', Newsletter::TYPE_WELCOME);
    $newsletterOption = $this->_createNewsletterOption($newsletterOptionField->id, $newsletter->id, 'immediately');
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_WELCOME)
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->loggerFactory, $this->cronHelper);

    // queue and associated newsletter should be deleted when interval type is set to "immediately"
    expect(SendingQueue::findMany())->notEmpty();
    $scheduler->deleteQueueOrUpdateNextRunDate($queue, $newsletter);
    expect(SendingQueue::findMany())->count(0);
  }

  public function testItCanRescheduleQueueDeliveryTime() {
    $newsletter = $this->_createNewsletter();
    $newsletterOptionField =
      $this->_createNewsletterOptionField('intervalType', Newsletter::TYPE_WELCOME);
    $newsletterOption = $this->_createNewsletterOption($newsletterOptionField->id, $newsletter->id, 'daily');
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_WELCOME)
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->loggerFactory, $this->cronHelper);

    // queue's next run date should change when interval type is set to anything
    // other than "immediately"
    $queue = $this->_createQueue($newsletter->id);
    $newsletterOption->value = 'daily';
    $newsletterOption->save();
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_WELCOME)->findOne($newsletter->id);
    expect($queue->scheduledAt)->null();
    $newsletter->schedule = '0 5 * * *'; // set it to daily at 5
    $scheduler->deleteQueueOrUpdateNextRunDate($queue, $newsletter);
    /** @var SendingQueue $queue */
    $queue = SendingQueue::findOne($queue->id);
    $queue = SendingTask::createFromQueue($queue);
    expect($queue->scheduledAt)->notNull();
  }

  public function testItFailsWPSubscriberVerificationWhenSubscriberIsNotAWPUser() {
    $wPUser = $this->_createOrUpdateWPUser('editor');
    $subscriber = $this->_createSubscriber();
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_WELCOME);
    $newsletterOptionField = $this->_createNewsletterOptionField(
      'role',
      Newsletter::TYPE_WELCOME
    );
    $newsletterOption = $this->_createNewsletterOption(
      $newsletterOptionField->id,
      $newsletter->id, 'author'
    );
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_WELCOME)
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->loggerFactory, $this->cronHelper);

    // return false and delete queue when subscriber is not a WP user
    $result = $scheduler->verifyWPSubscriber($subscriber->id, $newsletter, $queue);
    expect($result)->false();
    expect(SendingQueue::findMany())->count(0);
  }

  public function testItFailsWPSubscriberVerificationWhenSubscriberRoleDoesNotMatch() {
    $wPUser = $this->_createOrUpdateWPUser('editor');
    $subscriber = $this->_createSubscriber($wPUser->ID);
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_WELCOME);
    $newsletterOptionField = $this->_createNewsletterOptionField(
      'role',
      Newsletter::TYPE_WELCOME
    );
    $newsletterOption = $this->_createNewsletterOption(
      $newsletterOptionField->id,
      $newsletter->id, 'author'
    );
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_WELCOME)
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->loggerFactory, $this->cronHelper);

    // return false and delete queue when subscriber role is different from the one
    // specified for the welcome email
    $result = $scheduler->verifyWPSubscriber($subscriber->id, $newsletter, $queue);
    expect($result)->false();
    expect(count(SendingQueue::findMany()))->equals(0);
  }

  public function testItPassesWPSubscriberVerificationWhenSubscriberExistsAndRoleMatches() {
    $wPUser = $this->_createOrUpdateWPUser('author');
    $subscriber = $this->_createSubscriber($wPUser->ID);
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_WELCOME);
    $newsletterOptionField =
      $this->_createNewsletterOptionField('role', Newsletter::TYPE_WELCOME);
    $newsletterOption = $this->_createNewsletterOption(
      $newsletterOptionField->id,
      $newsletter->id, 'author'
    );
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_WELCOME)
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->loggerFactory, $this->cronHelper);

    // return true when user exists and WP role matches the one specified for the welcome email
    $result = $scheduler->verifyWPSubscriber($subscriber->id, $newsletter, $queue);
    expect($result)->true();
    expect(count(SendingQueue::findMany()))->greaterOrEquals(1);
  }

  public function testItPassesWPSubscriberVerificationWhenSubscriberHasAnyRole() {
    $wPUser = $this->_createOrUpdateWPUser('author');
    $subscriber = $this->_createSubscriber($wPUser->ID);
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_WELCOME);
    $newsletterOptionField =
      $this->_createNewsletterOptionField('role', Newsletter::TYPE_WELCOME);
    $newsletterOption = $this->_createNewsletterOption(
      $newsletterOptionField->id, $newsletter->id,
      WelcomeScheduler::WORDPRESS_ALL_ROLES
    );
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_WELCOME)
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->loggerFactory, $this->cronHelper);

    // true when user exists and has any role
    $result = $scheduler->verifyWPSubscriber($subscriber->id, $newsletter, $queue);
    expect($result)->true();
    expect(count(SendingQueue::findMany()))->greaterOrEquals(1);
  }

  public function testItDoesNotProcessWelcomeNewsletterWhenThereAreNoSubscribersToProcess() {
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->id);
    $queue->setSubscribers([]);

    // delete queue when the list of subscribers to process is blank
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->loggerFactory, $this->cronHelper);
    $result = $scheduler->processWelcomeNewsletter($newsletter, $queue);
    expect($result)->false();
    expect(SendingQueue::findMany())->count(0);
  }

  public function testItDoesNotProcessWelcomeNewsletterWhenWPUserCannotBeVerified() {
    $newsletter = $this->_createNewsletter();
    $newsletter->event = 'user';
    $queue = $this->_createQueue($newsletter->id);
    $queue->setSubscribers([1]);

    // return false when WP user cannot be verified
    $scheduler = Stub::make(Scheduler::class, [
      'verifyWPSubscriber' => Expected::exactly(1, function() {
        return false;
      }),
    ], $this);
    expect($scheduler->processWelcomeNewsletter($newsletter, $queue))->false();
  }

  public function testItDoesNotProcessWelcomeNewsletterWhenSubscriberCannotBeVerified() {
    $newsletter = $this->_createNewsletter();
    $newsletter->event = 'segment';
    $queue = $this->_createQueue($newsletter->id);
    $queue->setSubscribers([1]);

    // return false when subscriber cannot be verified
    $scheduler = Stub::make(Scheduler::class, [
      'verifyMailpoetSubscriber' => Expected::exactly(1, function() {
        return false;
      }),
    ], $this);
    expect($scheduler->processWelcomeNewsletter($newsletter, $queue))->false();
  }

  public function testItProcessesWelcomeNewsletterWhenSubscriberIsVerified() {
    $newsletter = $this->_createNewsletter();
    $newsletter->event = 'segment';

    // return true when subsriber is verified and update the queue's status to null
    $queue = $this->_createQueue($newsletter->id);
    $queue->setSubscribers([1]);
    $scheduler = Stub::make(Scheduler::class, [
      'verifyMailpoetSubscriber' => Expected::exactly(1),
    ], $this);
    expect($queue->status)->notNull();
    expect($scheduler->processWelcomeNewsletter($newsletter, $queue))->true();
    /** @var SendingQueue $updatedQueue */
    $updatedQueue = SendingQueue::findOne($queue->id);
    $updatedQueue = SendingTask::createFromQueue($updatedQueue);
    expect($updatedQueue->status)->null();
  }

  public function testItProcessesWelcomeNewsletterWhenWPUserIsVerified() {
    $newsletter = $this->_createNewsletter();
    $newsletter->event = 'user';

    // return true when WP user is verified
    $queue = $this->_createQueue($newsletter->id);
    $queue->setSubscribers([1]);
    $scheduler = Stub::make(Scheduler::class, [
      'verifyWPSubscriber' => Expected::exactly(1),
    ], $this);
    expect($queue->status)->notNull();
    expect($scheduler->processWelcomeNewsletter($newsletter, $queue))->true();
    // update queue's status to null
    /** @var SendingQueue $updatedQueue */
    $updatedQueue = SendingQueue::findOne($queue->id);
    $updatedQueue = SendingTask::createFromQueue($updatedQueue);
    expect($updatedQueue->status)->null();
  }

  public function testItFailsMailpoetSubscriberVerificationWhenSubscriberDoesNotExist() {
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->loggerFactory, $this->cronHelper);
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->id);

    // return false
    $result = $scheduler->verifyMailpoetSubscriber(null, $newsletter, $queue);
    expect($result)->false();
    // delete queue when subscriber can't be found
    expect(SendingQueue::findMany())->count(0);
  }

  public function testItFailsMailpoetSubscriberVerificationWhenSubscriberIsNotInSegment() {
    $subscriber = $this->_createSubscriber();
    $segment = $this->_createSegment();
    $newsletter = $this->_createNewsletter();
    $newsletterOptionField = $this->_createNewsletterOptionField('segment', Newsletter::TYPE_NOTIFICATION);
    $newsletterOption = $this->_createNewsletterOption($newsletterOptionField->id, $newsletter->id, $segment->id);
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_NOTIFICATION)
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->loggerFactory, $this->cronHelper);

    // return false
    $result = $scheduler->verifyMailpoetSubscriber($subscriber->id, $newsletter, $queue);
    expect($result)->false();
    // delete queue when subscriber is not in segment specified for the newsletter
    expect(SendingQueue::findMany())->count(0);
  }

  public function testItReschedulesQueueDeliveryWhenMailpoetSubscriberHasNotConfirmedSubscription() {
    $timestamp = WPFunctions::get()->currentTime('timestamp');
    $wpFunctions = $this->make(WPFunctions::class, [
      'currentTime' => $timestamp,
    ]);
    WPFunctions::set($wpFunctions);
    $currentTime = Carbon::createFromTimestamp($timestamp);
    Carbon::setTestNow($currentTime); // mock carbon to return current time

    $subscriber = $this->_createSubscriber($wpUserId = null, 'unconfirmed');
    $segment = $this->_createSegment();
    $subscriberSegment = $this->_createSubscriberSegment($subscriber->id, $segment->id);
    $newsletter = $this->_createNewsletter();
    $newsletterOptionField =
      $this->_createNewsletterOptionField('segment', Newsletter::TYPE_NOTIFICATION);
    $newsletterOption = $this->_createNewsletterOption(
      $newsletterOptionField->id, $newsletter->id,
      $segment->id
    );
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_NOTIFICATION)
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->loggerFactory, $this->cronHelper);

    // return false
    $result = $scheduler->verifyMailpoetSubscriber($subscriber->id, $newsletter, $queue);
    expect($result)->false();
    // update the time queue is scheduled to run at
    /** @var SendingQueue $updatedQueue */
    $updatedQueue = SendingQueue::findOne($queue->id);
    $updatedQueue = SendingTask::createFromQueue($updatedQueue);
    expect(Carbon::parse($updatedQueue->scheduledAt))->equals(
      $currentTime->addMinutes(ScheduledTask::BASIC_RESCHEDULE_TIMEOUT)
    );
    WPFunctions::set(new WPFunctions());
  }

  public function testItDoesntRunQueueDeliveryWhenMailpoetSubscriberHasUnsubscribed() {
    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    Carbon::setTestNow($currentTime); // mock carbon to return current time
    $subscriber = $this->_createSubscriber($wpUserId = null, 'unsubscribed');
    $segment = $this->_createSegment();
    $subscriberSegment = $this->_createSubscriberSegment($subscriber->id, $segment->id);
    $newsletter = $this->_createNewsletter();
    $newsletterOptionField =
      $this->_createNewsletterOptionField('segment', Newsletter::TYPE_NOTIFICATION);
    $newsletterOption = $this->_createNewsletterOption(
      $newsletterOptionField->id, $newsletter->id,
      $segment->id
    );
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_NOTIFICATION)
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->loggerFactory, $this->cronHelper);

    // return false
    $result = $scheduler->verifyMailpoetSubscriber($subscriber->id, $newsletter, $queue);
    expect($result)->false();
    // update the time queue is scheduled to run at
    expect(SendingQueue::findOne($queue->id))->false();
  }

  public function testItCanVerifyMailpoetSubscriber() {
    $subscriber = $this->_createSubscriber();
    $segment = $this->_createSegment();
    $subscriberSegment = $this->_createSubscriberSegment($subscriber->id, $segment->id);
    $newsletter = $this->_createNewsletter();
    $newsletterOptionField =
      $this->_createNewsletterOptionField('segment', Newsletter::TYPE_NOTIFICATION);
    $newsletterOption = $this->_createNewsletterOption(
      $newsletterOptionField->id, $newsletter->id,
      $segment->id
    );
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_NOTIFICATION)
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->loggerFactory, $this->cronHelper);

    // return true after successful verification
    $result = $scheduler->verifyMailpoetSubscriber($subscriber->id, $newsletter, $queue);
    expect($result)->true();
  }

  public function testItProcessesScheduledStandardNewsletter() {
    $subscriber = $this->_createSubscriber();
    $segment = $this->_createSegment();
    $subscriberSegment = $this->_createSubscriberSegment($subscriber->id, $segment->id);
    $newsletter = $this->_createNewsletter();
    $newsletterSegment = $this->_createNewsletterSegment($newsletter->id, $segment->id);
    $newsletterOptionField =
      $this->_createNewsletterOptionField('segment', Newsletter::TYPE_NOTIFICATION);
    $newsletterOption = $this->_createNewsletterOption(
      $newsletterOptionField->id, $newsletter->id,
      $segment->id
    );
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_NOTIFICATION)
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler($this->subscribersFinder, $this->loggerFactory, $this->cronHelper);

    // return true
    expect($scheduler->processScheduledStandardNewsletter($newsletter, $queue))->true();
    // update queue's list of subscribers to process
    /** @var SendingQueue $updatedQueue */
    $updatedQueue = SendingQueue::findOne($queue->id);
    $updatedQueue = SendingTask::createFromQueue($updatedQueue);
    $updatedQueueSubscribers = $updatedQueue->getSubscribers(ScheduledTaskSubscriber::STATUS_UNPROCESSED);
    expect($updatedQueueSubscribers)->equals([$subscriber->id]);
    // set queue's status to null
    expect($updatedQueue->status)->null();
    // set newsletter's status to sending
    $updatedNewsletter = Newsletter::findOne($newsletter->id);
    expect($updatedNewsletter->status)->equals(Newsletter::STATUS_SENDING);
  }

  public function testItFailsToProcessPostNotificationNewsletterWhenSegmentsDontExist() {
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->id);

    // delete or reschedule queue when segments don't exist
    $scheduler = Stub::make(Scheduler::class, [
      'deleteQueueOrUpdateNextRunDate' => Expected::exactly(1, function() {
        return false;
      }),
      'loggerFactory' => $this->loggerFactory,
      'cronHelper' => $this->cronHelper,
    ], $this);
    expect($scheduler->processPostNotificationNewsletter($newsletter, $queue))->false();
  }

  public function testItFailsToProcessPostNotificationNewsletterWhenSubscribersNotInSegment() {
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->id);
    $segment = $this->_createSegment();
    $newsletterSegment = $this->_createNewsletterSegment($newsletter->id, $segment->id);

    // delete or reschedule queue when there are no subscribers in segments
    $scheduler = $this->construct(Scheduler::class, [$this->subscribersFinder, $this->loggerFactory, $this->cronHelper], [
      'deleteQueueOrUpdateNextRunDate' => Expected::exactly(1, function() {
        return false;
      }),
    ]);
    expect($scheduler->processPostNotificationNewsletter($newsletter, $queue))->false();
  }

  public function testItCanProcessPostNotificationNewsletter() {
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->id);
    $segment = $this->_createSegment();
    $newsletterSegment = $this->_createNewsletterSegment($newsletter->id, $segment->id);
    $subscriber = $this->_createSubscriber();
    $subscriberSegment = $this->_createSubscriberSegment($subscriber->id, $segment->id);
    $newsletterOptionField =
      $this->_createNewsletterOptionField('segment', Newsletter::TYPE_NOTIFICATION);
    $newsletterOption = $this->_createNewsletterOption(
      $newsletterOptionField->id, $newsletter->id,
      $segment->id
    );
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_NOTIFICATION)
      ->findOne($newsletter->id);
    $scheduler = new Scheduler($this->subscribersFinder, $this->loggerFactory, $this->cronHelper);

    // return true
    expect($scheduler->processPostNotificationNewsletter($newsletter, $queue))->true();
    // create notification history
    $notificationHistory = Newsletter::where('parent_id', $newsletter->id)
      ->findOne();
    expect($notificationHistory)->notEmpty();
    // update queue with a list of subscribers to process and change newsletter id
    // to that of the notification history
    /** @var SendingQueue $updatedQueue */
    $updatedQueue = SendingQueue::findOne($queue->id);
    $updatedQueue = SendingTask::createFromQueue($updatedQueue);
    $updatedQueueSubscribers = $updatedQueue->getSubscribers(ScheduledTaskSubscriber::STATUS_UNPROCESSED);
    expect($updatedQueueSubscribers)->equals([$subscriber->id]);
    expect($updatedQueue->newsletterId)->equals($notificationHistory->id);
    // set notification history's status to sending
    $updatedNotificationHistory = Newsletter::where('parent_id', $newsletter->id)
      ->findOne();
    expect($updatedNotificationHistory->status)->equals(Newsletter::STATUS_SENDING);
  }

  public function testItFailsToProcessWhenScheduledQueuesNotFound() {
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->loggerFactory, $this->cronHelper);
    expect($scheduler->process())->false();
  }

  public function testItDeletesQueueDuringProcessingWhenNewsletterNotFound() {
    $queue = $this->_createQueue(1);
    $queue->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->loggerFactory, $this->cronHelper);
    $scheduler->process();
    expect(SendingQueue::findMany())->count(0);
  }

  public function testItDeletesQueueDuringProcessingWhenNewsletterIsSoftDeleted() {
    $newsletter = $this->_createNewsletter();
    $newsletter->deletedAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $newsletter->save();
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->loggerFactory, $this->cronHelper);
    $scheduler->process();
    expect(SendingQueue::findMany())->count(0);
  }

  public function testItProcessesWelcomeNewsletters() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_WELCOME);
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();
    $scheduler = Stub::make(Scheduler::class, [
      'processWelcomeNewsletter' => Expected::exactly(1),
      'cronHelper' => $this->cronHelper,
    ], $this);
    $scheduler->process();
  }

  public function testItProcessesNotificationNewsletters() {
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();
    $scheduler = Stub::make(Scheduler::class, [
      'processPostNotificationNewsletter' => Expected::exactly(1),
      'cronHelper' => $this->cronHelper,
    ], $this);
    $scheduler->process();
  }

  public function testItProcessesStandardScheduledNewsletters() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_STANDARD);
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();
    $scheduler = Stub::make(Scheduler::class, [
      'processScheduledStandardNewsletter' => Expected::exactly(1),
      'cronHelper' => $this->cronHelper,
    ], $this);
    $scheduler->process();
  }

  public function testItEnforcesExecutionLimitDuringProcessing() {
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();
    $scheduler = Stub::make(Scheduler::class, [
      'processPostNotificationNewsletter' => Expected::exactly(1),
      'cronHelper' => $this->make(CronHelper::class, [
        'enforceExecutionLimit' => Expected::exactly(2), // call at start + during processing
      ]),
    ], $this);
    $scheduler->process();
  }

  public function testItDoesNotProcessScheduledJobsWhenNewsletterIsNotActive() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_STANDARD, Newsletter::STATUS_DRAFT);
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();

    $scheduler = Stub::make(Scheduler::class, [
      'processScheduledStandardNewsletter' => Expected::never(),
      'cronHelper' => $this->cronHelper,
    ], $this);
    // scheduled job is not processed
    $scheduler->process();
  }

  public function testItProcessesScheduledJobsWhenNewsletterIsActive() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_STANDARD, Newsletter::STATUS_ACTIVE);
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();

    $scheduler = Stub::make(Scheduler::class, [
      'processScheduledStandardNewsletter' => Expected::once(),
      'cronHelper' => $this->cronHelper,
    ], $this);
    // scheduled job is processed
    $scheduler->process();
  }

  public function testItDoesNotReSchedulesBounceTaskWhenSoon() {
    $task = ScheduledTask::createOrUpdate([
      'type' => 'bounce',
      'status' => ScheduledTask::STATUS_SCHEDULED,
      'scheduled_at' => Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->addMinute(5),
    ]);
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_STANDARD, Newsletter::STATUS_DRAFT);
    $queue = $this->_createQueue($newsletter->id);
    $finder = $this->makeEmpty(SubscribersFinder::class);
    $scheduler = new Scheduler($finder, $this->loggerFactory, $this->cronHelper);

    $scheduler->processScheduledStandardNewsletter($newsletter, $queue);
    $refetchedTask = ScheduledTask::where('id', $task->id)->findOne();
    expect($refetchedTask->scheduledAt)->lessThan(Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->addHours(1));
  }

  public function testItProcessesScheduledJobsWhenNewsletterIsScheduled() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_STANDARD, Newsletter::STATUS_SCHEDULED);
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();

    $scheduler = Stub::make(Scheduler::class, [
      'processScheduledStandardNewsletter' => Expected::once(),
      'cronHelper' => $this->cronHelper,
    ], $this);
    // scheduled job is processed
    $scheduler->process();
  }

  public function testItProcessesScheduledAutomaticEmailWhenSendingToUser() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_AUTOMATIC, Newsletter::STATUS_SCHEDULED);
    $subscriber = $this->_createSubscriber();
    $task = SendingTask::create();
    $task->newsletterId = $newsletter->id;
    $task->status = SendingQueue::STATUS_SCHEDULED;
    $task->scheduledAt = Carbon::now()->subDay(1)->toDateTimeString();
    $task->setSubscribers([$subscriber->id]);
    $task->save();

    // scheduled task should exist
    $task = SendingTask::getByNewsletterId($newsletter->id);
    expect($task->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect($task->newsletterId)->equals($newsletter->id);
    expect($task->getSubscribers())->equals([$subscriber->id]);

    // task should have its status set to null (i.e., sending)
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->loggerFactory, $this->cronHelper);
    $scheduler->process();
    $task = SendingTask::getByNewsletterId($newsletter->id);
    expect($task->status)->null();
  }

  public function testItDeletesScheduledAutomaticEmailWhenUserDoesNotExist() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_AUTOMATIC, Newsletter::STATUS_SCHEDULED);
    $subscriber = $this->_createSubscriber();
    $task = SendingTask::create();
    $task->newsletterId = $newsletter->id;
    $task->status = SendingQueue::STATUS_SCHEDULED;
    $task->scheduledAt = Carbon::now()->subDay(1)->toDateTimeString();
    $task->setSubscribers([$subscriber->id]);
    $task->save();
    $subscriber->delete();

    // scheduled task should exist
    $task = SendingTask::getByNewsletterId($newsletter->id);
    expect($task->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect($task->newsletterId)->equals($newsletter->id);
    expect($task->getSubscribers())->equals([$subscriber->id]);

    // task should be deleted
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->loggerFactory, $this->cronHelper);
    $scheduler->process();
    $task = SendingTask::getByNewsletterId($newsletter->id);
    expect($task)->false();
  }

  public function testItProcessesScheduledAutomaticEmailWhenSendingToSegment() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_AUTOMATIC, Newsletter::STATUS_SCHEDULED);
    $segment = $this->_createSegment();
    $subscriber = $this->_createSubscriber();
    $segmentSubscriber = $this->_createSubscriberSegment($subscriber->id, $segment->id);
    $options = ['sendTo' => 'segment', 'segment' => $segment->id];
    foreach ($options as $option => $value) {
      $newsletterOptionField = $this->_createNewsletterOptionField($option, Newsletter::TYPE_AUTOMATIC);
      $newsletterOption = $this->_createNewsletterOption($newsletterOptionField->id, $newsletter->id, $value);
    }
    $task = SendingTask::create();
    $task->newsletterId = $newsletter->id;
    $task->status = SendingQueue::STATUS_SCHEDULED;
    $task->scheduledAt = Carbon::now()->subDay(1)->toDateTimeString();
    $task->save();

    // scheduled task should exist
    $task = SendingTask::getByNewsletterId($newsletter->id);
    expect($task->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect($task->newsletterId)->equals($newsletter->id);

    // task should have its status set to null (i.e., sending)
    $scheduler = new Scheduler($this->subscribersFinder, $this->loggerFactory, $this->cronHelper);
    $scheduler->process();
    $task = SendingTask::getByNewsletterId($newsletter->id);
    expect($task->status)->null();
    // task should have 1 subscriber added from segment
    $subscribers = $task->subscribers()->findMany();
    expect($subscribers)->count(1);
    expect($subscribers[0]->id)->equals($subscriber->id);
  }

  public function testItUpdatesUpdateTime() {
    $originalUpdated = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->subHours(5)->toDateTimeString();
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_WELCOME, Newsletter::STATUS_DRAFT);
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->updatedAt = $originalUpdated;
    $queue->save();
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->loggerFactory, $this->cronHelper);
    $scheduler->process();
    $newQueue = ScheduledTask::findOne($queue->taskId);
    expect($newQueue->updatedAt)->notEquals($originalUpdated);
  }

  public function _createNewsletterSegment($newsletterId, $segmentId) {
    $newsletterSegment = NewsletterSegment::create();
    $newsletterSegment->newsletterId = $newsletterId;
    $newsletterSegment->segmentId = $segmentId;
    $newsletterSegment->save();
    expect($newsletterSegment->getErrors())->false();
    return $newsletterSegment;
  }

  public function _createSubscriberSegment($subscriberId, $segmentId, $status = 'subscribed') {
    $subscriberSegment = SubscriberSegment::create();
    $subscriberSegment->subscriberId = $subscriberId;
    $subscriberSegment->segmentId = $segmentId;
    $subscriberSegment->status = $status;
    $subscriberSegment->save();
    expect($subscriberSegment->getErrors())->false();
    return $subscriberSegment;
  }

  public function _createSegment() {
    $segment = Segment::create();
    $segment->name = 'test';
    $segment->type = 'default';
    $segment->save();
    expect($segment->getErrors())->false();
    return $segment;
  }

  public function _createSubscriber($wpUserId = null, $status = 'subscribed') {
    $subscriber = Subscriber::create();
    $subscriber->email = 'john@doe.com';
    $subscriber->firstName = 'John';
    $subscriber->lastName = 'Doe';
    $subscriber->wpUserId = $wpUserId;
    $subscriber->status = $status;
    $subscriber->save();
    expect($subscriber->getErrors())->false();
    return $subscriber;
  }

  public function _createNewsletter($type = Newsletter::TYPE_NOTIFICATION, $status = 'active') {
    $newsletter = Newsletter::create();
    $newsletter->type = $type;
    $newsletter->status = $status;
    $newsletter->save();
    expect($newsletter->getErrors())->false();
    return $newsletter;
  }

  public function _createNewsletterOptionField($name, $type) {
    $newsletterOptionField = NewsletterOptionField::create();
    $newsletterOptionField->name = $name;
    $newsletterOptionField->newsletterType = $type;
    $newsletterOptionField->save();
    expect($newsletterOptionField->getErrors())->false();
    return $newsletterOptionField;
  }

  public function _createNewsletterOption($optionFieldId, $newsletterId, $value) {
    $newsletterOption = NewsletterOption::create();
    $newsletterOption->optionFieldId = $optionFieldId;
    $newsletterOption->newsletterId = $newsletterId;
    $newsletterOption->value = $value;
    $newsletterOption->save();
    expect($newsletterOption->getErrors())->false();
    return $newsletterOption;
  }

  public function _createQueue($newsletterId, $status = SendingQueue::STATUS_SCHEDULED) {
    $queue = SendingTask::create();
    $queue->status = $status;
    $queue->newsletterId = $newsletterId;
    $queue->save();
    expect($queue->getErrors())->false();
    return $queue;
  }

  public function _createOrUpdateWPUser($role = null) {
    $email = 'test@example.com';
    $username = 'phoenix_test_user';
    if (email_exists($email) === false) {
      wp_insert_user(
        [
          'user_login' => $username,
          'user_email' => $email,
          'user_pass' => null,
        ]
      );
    }
    $user = get_user_by('login', $username);
    wp_update_user(
      [
        'ID' => $user->ID,
        'role' => $role,
      ]
    );
    expect($user->ID)->notNull();
    return $user;
  }

  public function _after() {
    Carbon::setTestNow();
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    $this->diContainer->get(SettingsRepository::class)->truncate();
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterSegment::$_table);
  }
}
