<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\CronWorkerScheduler;
use MailPoet\Cron\Workers\Scheduler;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\NewsletterSegmentEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Scheduler\Scheduler as NewsletterScheduler;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Newsletter\Segment\NewsletterSegmentRepository;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\SubscribersFinder;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\NewsletterOption as NewsletterOptionFactory;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use WP_User;

class SchedulerTest extends \MailPoetTest {
  public $cronHelper;
  /** @var LoggerFactory */
  private $loggerFactory;

  /** @var SubscribersFinder */
  private $subscribersFinder;

  /** @var CronWorkerScheduler */
  private $cronWorkerScheduler;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var NewsletterScheduler */
  private $newsletterScheduler;

  /** @var NewsletterOptionFactory */
  private $newsletterOptionFactory;

  /** @var NewsletterSegmentRepository */
  private $newsletterSegmentRepository;

  /** @var Security */
  private $security;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  /** @var SubscriberFactory */
  private $subscriberFactory;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SegmentFactory */
  private $segmentFactory;

  /** @var ScheduledTaskFactory */
  private $scheduledTaskFactory;

  /** @var NewsletterFactory */
  private $newsletterFactory;

  public function _before() {
    parent::_before();
    $this->loggerFactory = LoggerFactory::getInstance();
    $this->cronHelper = $this->diContainer->get(CronHelper::class);
    $this->subscribersFinder = $this->diContainer->get(SubscribersFinder::class);
    $this->cronWorkerScheduler = $this->diContainer->get(CronWorkerScheduler::class);
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->newsletterScheduler = $this->diContainer->get(NewsletterScheduler::class);
    $this->newsletterOptionFactory = new NewsletterOptionFactory();
    $this->newsletterSegmentRepository = $this->diContainer->get(NewsletterSegmentRepository::class);
    $this->security = $this->diContainer->get(Security::class);
    $this->sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);
    $this->subscriberSegmentRepository = $this->diContainer->get(SubscriberSegmentRepository::class);
    $this->subscriberFactory = new SubscriberFactory();
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->segmentFactory = new SegmentFactory();
    $this->scheduledTaskFactory = new ScheduledTaskFactory();
    $this->newsletterFactory = new NewsletterFactory();
  }

  public function testItThrowsExceptionWhenExecutionLimitIsReached() {
    try {
      $scheduler = $this->getScheduler();
      $scheduler->process(microtime(true) - $this->cronHelper->getDaemonExecutionLimit());
      self::fail('Maximum execution time limit exception was not thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->startsWith('The maximum execution time');
    }
  }

  public function testItCanGetScheduledQueues() {
    $scheduler = $this->diContainer->get(Scheduler::class);
    expect($scheduler->getScheduledSendingTasks())->isEmpty();
    $queue = SendingTask::create();
    $queue->newsletterId = 1;
    $queue->status = SendingQueueEntity::STATUS_SCHEDULED;
    $queue->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();
    expect($scheduler->getScheduledSendingTasks())->notEmpty();
  }

  public function testItCanCreateNotificationHistory() {
    $segments[] = $this->segmentFactory
      ->withName('Segment A')
      ->create();
    $segments[] = $this->segmentFactory
      ->withName('Segment B')
      ->create();
    $newsletter = $this->newsletterFactory
      ->withType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY)
      ->withSegments($segments)
      ->create();

    // ensure that notification history does not exist
    $notificationHistory = $this->newslettersRepository->findOneBy(['parent' => $newsletter, 'type' => NewsletterEntity::TYPE_NOTIFICATION_HISTORY]);
    expect($notificationHistory)->isEmpty();

    // create notification history and ensure that it exists
    $scheduler = $this->getScheduler();
    $scheduler->createPostNotificationHistory($newsletter);
    $notificationHistory = $this->newslettersRepository->findOneBy(['parent' => $newsletter, 'type' => NewsletterEntity::TYPE_NOTIFICATION_HISTORY]);
    expect($notificationHistory)->notEmpty();
    $this->assertInstanceOf(NewsletterEntity::class, $notificationHistory);
    // check the hash of the post notification history
    expect($notificationHistory->getHash())->notEquals($newsletter->getHash());
    verify(strlen((string)$notificationHistory->getHash()))->equals(Security::HASH_LENGTH);
    // check the unsubscribe token of the post notification history
    expect($notificationHistory->getUnsubscribeToken())->notEquals($newsletter->getUnsubscribeToken());
    verify(strlen((string)$notificationHistory->getUnsubscribeToken()))->equals(Security::UNSUBSCRIBE_TOKEN_LENGTH);

    verify($notificationHistory->getParent())->equals($newsletter);
    expect($notificationHistory->getNewsletterSegments())->count(count($segments));
  }

  public function testItCanDeleteQueueWhenDeliveryIsSetToImmediately() {
    $newsletter = $this->_createNewsletter();
    $newsletterEntity = $this->entityManager->getReference(NewsletterEntity::class, $newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $this->newsletterOptionFactory->create($newsletterEntity, 'intervalType', 'immediately');

    $newsletter = $this->newslettersRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $queue = $this->_createQueue($newsletter->getId());
    $scheduler = $this->getScheduler();

    // queue and associated newsletter should be deleted when interval type is set to "immediately"
    expect($this->sendingQueuesRepository->findAll())->notEmpty();
    $scheduler->deleteQueueOrUpdateNextRunDate($queue, $newsletter);
    expect($this->sendingQueuesRepository->findAll())->count(0);
  }

  public function testItCanRescheduleQueueDeliveryTime() {
    $newsletter = $this->_createNewsletter();
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $newsletterOption = $this->newsletterOptionFactory->create($newsletter, 'intervalType', 'daily');

    $newsletter = $this->newslettersRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $queue = $this->_createQueue($newsletter->getId());
    $scheduler = $this->getScheduler();

    // queue's next run date should change when interval type is set to anything
    // other than "immediately"
    $queue = $this->_createQueue($newsletter->getId());
    $newsletterOption->setValue('daily');
    $this->entityManager->persist($newsletterOption);
    $this->entityManager->flush();

    $newsletter = $this->newslettersRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    verify($queue->scheduledAt)->null();

    $this->newsletterOptionFactory->create($newsletter, NewsletterOptionFieldEntity::NAME_SCHEDULE, '0 5 * * *');

    $scheduler->deleteQueueOrUpdateNextRunDate($queue, $newsletter);

    $queueEntity = $this->sendingQueuesRepository->findOneById($queue->id);
    $this->assertInstanceOf(SendingQueueEntity::class, $queueEntity);
    $scheduledTask = $this->scheduledTasksRepository->findOneBySendingQueue($queueEntity);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    expect($scheduledTask->getScheduledAt())->notNull();
  }

  public function testItFailsWPSubscriberVerificationWhenSubscriberIsNotAWPUser() {
    $wPUser = $this->_createOrUpdateWPUser('editor');
    $subscriber = $this->_createSubscriber();
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_WELCOME);

    $this->newsletterOptionFactory->create($newsletter, 'role', 'author');

    $queue = $this->_createQueue($newsletter->getId());
    $scheduler = $this->getScheduler();

    // return false and delete queue when subscriber is not a WP user
    $result = $scheduler->verifyWPSubscriber((int)$subscriber->getId(), $newsletter, $queue);
    verify($result)->false();
    expect($this->sendingQueuesRepository->findAll())->count(0);
  }

  public function testItFailsWPSubscriberVerificationWhenSubscriberRoleDoesNotMatch() {
    $wPUser = $this->_createOrUpdateWPUser('editor');
    $subscriber = $this->_createSubscriber($wPUser->ID);
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_WELCOME);

    $this->newsletterOptionFactory->create($newsletter, 'role', 'author');

    $queue = $this->_createQueue($newsletter->getId());
    $scheduler = $this->getScheduler();

    // return false and delete queue when subscriber role is different from the one
    // specified for the welcome email
    $result = $scheduler->verifyWPSubscriber((int)$subscriber->getId(), $newsletter, $queue);
    verify($result)->false();
    expect($this->sendingQueuesRepository->findAll())->count(0);
  }

  public function testItPassesWPSubscriberVerificationWhenSubscriberExistsAndRoleMatches() {
    $wPUser = $this->_createOrUpdateWPUser('author');
    $subscriber = $this->_createSubscriber($wPUser->ID);
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_WELCOME);

    $this->newsletterOptionFactory->create($newsletter, 'role', 'author');

    $queue = $this->_createQueue($newsletter->getId());
    $scheduler = $this->getScheduler();

    // return true when user exists and WP role matches the one specified for the welcome email
    $result = $scheduler->verifyWPSubscriber((int)$subscriber->getId(), $newsletter, $queue);
    verify($result)->true();
    expect(count($this->sendingQueuesRepository->findAll()))->greaterOrEquals(1);
  }

  public function testItPassesWPSubscriberVerificationWhenSubscriberHasAnyRole() {
    $wPUser = $this->_createOrUpdateWPUser('author');
    $subscriber = $this->_createSubscriber($wPUser->ID);
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_WELCOME);

    $this->newsletterOptionFactory->create($newsletter, 'role', WelcomeScheduler::WORDPRESS_ALL_ROLES);

    $queue = $this->_createQueue($newsletter->getId());
    $scheduler = $this->getScheduler();

    // true when user exists and has any role
    $result = $scheduler->verifyWPSubscriber((int)$subscriber->getId(), $newsletter, $queue);
    verify($result)->true();
    expect(count($this->sendingQueuesRepository->findAll()))->greaterOrEquals(1);
  }

  public function testItDoesNotProcessWelcomeNewsletterWhenThereAreNoSubscribersToProcess() {
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->getId());
    $queue->setSubscribers([]);

    // delete queue when the list of subscribers to process is blank
    $scheduler = $this->getScheduler();
    $result = $scheduler->processWelcomeNewsletter($newsletter, $queue);
    verify($result)->false();
    expect($this->sendingQueuesRepository->findAll())->count(0);
  }

  public function testItDoesNotProcessWelcomeNewsletterWhenWPUserCannotBeVerified() {
    $newsletter = $this->_createNewsletter();
    $this->newsletterOptionFactory->create($newsletter, 'event', 'user');

    $queue = $this->_createQueue($newsletter->getId());
    $queue->setSubscribers([1]);

    // return false when WP user cannot be verified
    $scheduler = Stub::make(Scheduler::class, [
      'verifyWPSubscriber' => Expected::exactly(1, function() {
        return false;
      }),
    ], $this);
    verify($scheduler->processWelcomeNewsletter($newsletter, $queue))->false();
  }

  public function testItDoesNotProcessWelcomeNewsletterWhenSubscriberCannotBeVerified() {
    $newsletter = $this->_createNewsletter();
    $this->newsletterOptionFactory->create($newsletter, 'event', 'segment');
    $queue = $this->_createQueue($newsletter->getId());
    $queue->setSubscribers([1]);

    // return false when subscriber cannot be verified
    $scheduler = Stub::make(Scheduler::class, [
      'verifyMailpoetSubscriber' => Expected::exactly(1, function() {
        return false;
      }),
    ], $this);
    verify($scheduler->processWelcomeNewsletter($newsletter, $queue))->false();
  }

  public function testItProcessesWelcomeNewsletterWhenSubscriberIsVerified() {
    $newsletter = $this->_createNewsletter();
    $this->newsletterOptionFactory->create($newsletter, 'event', 'segment');

    // return true when subsriber is verified and update the queue's status to null
    $queue = $this->_createQueue($newsletter->getId());
    $queue->setSubscribers([1]);
    $scheduler = Stub::make(Scheduler::class, [
      'verifyMailpoetSubscriber' => Expected::exactly(1, true),
      'scheduledTasksRepository' => $this->diContainer->get(ScheduledTasksRepository::class),
    ], $this);
    expect($queue->status)->notNull();
    verify($scheduler->processWelcomeNewsletter($newsletter, $queue))->true();
    $sendingQueue = $this->sendingQueuesRepository->findOneById($queue->id);
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $scheduledTask = $this->scheduledTasksRepository->findOneBySendingQueue($sendingQueue);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    verify($scheduledTask->getStatus())->null();
  }

  public function testItProcessesWelcomeNewsletterWhenWPUserIsVerified() {
    $newsletter = $this->_createNewsletter();
    $this->newsletterOptionFactory->create($newsletter, 'event', 'user');

    // return true when WP user is verified
    $queue = $this->_createQueue($newsletter->getId());
    $queue->setSubscribers([1]);
    $scheduler = Stub::make(Scheduler::class, [
      'verifyWPSubscriber' => Expected::exactly(1, true),
      'scheduledTasksRepository' => $this->diContainer->get(ScheduledTasksRepository::class),
    ], $this);
    expect($queue->status)->notNull();
    verify($scheduler->processWelcomeNewsletter($newsletter, $queue))->true();
    // update queue's status to null
    $sendingQueue = $this->sendingQueuesRepository->findOneById($queue->id);
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $scheduledTask = $this->scheduledTasksRepository->findOneBySendingQueue($sendingQueue);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    verify($scheduledTask->getStatus())->null();
  }

  public function testItFailsMailpoetSubscriberVerificationWhenSubscriberDoesNotExist() {
    $scheduler = $this->getScheduler();
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->getId());

    // return false
    $result = $scheduler->verifyMailpoetSubscriber(PHP_INT_MAX, $newsletter, $queue);
    verify($result)->false();
    // delete queue when subscriber can't be found
    expect($this->sendingQueuesRepository->findAll())->count(0);
  }

  public function testItFailsMailpoetSubscriberVerificationWhenSubscriberIsNotInSegment() {
    $subscriber = $this->_createSubscriber();
    $segment = $this->_createSegment();
    $newsletter = $this->_createNewsletter();

    $this->assertIsInt($segment->getId());
    $this->newsletterOptionFactory->create($newsletter, 'segment', $segment->getId());

    $queue = $this->_createQueue($newsletter->getId());
    $scheduler = $this->getScheduler();

    // return false
    $result = $scheduler->verifyMailpoetSubscriber((int)$subscriber->getId(), $newsletter, $queue);
    verify($result)->false();
    // delete queue when subscriber is not in segment specified for the newsletter
    expect($this->sendingQueuesRepository->findAll())->count(0);
  }

  public function testItReschedulesQueueDeliveryWhenMailpoetSubscriberHasNotConfirmedSubscription() {
    $timestamp = WPFunctions::get()->currentTime('timestamp');
    $wpFunctions = $this->make(WPFunctions::class, [
      'currentTime' => $timestamp,
    ]);
    WPFunctions::set($wpFunctions);
    $currentTime = Carbon::createFromTimestamp($timestamp);
    Carbon::setTestNow($currentTime); // mock carbon to return current time

    $subscriber = $this->_createSubscriber(0, 'unconfirmed');
    $segment = $this->_createSegment();
    $this->_createSubscriberSegment($subscriber->getId(), $segment->getId());
    $newsletter = $this->_createNewsletter();

    $this->assertIsInt($segment->getId());
    $this->newsletterOptionFactory->create($newsletter, 'segment', $segment->getId());

    $queue = $this->_createQueue($newsletter->getId());
    $scheduler = $this->getScheduler();

    // return false
    $result = $scheduler->verifyMailpoetSubscriber((int)$subscriber->getId(), $newsletter, $queue);
    verify($result)->false();
    // update the time queue is scheduled to run at
    $sendingQueue = $this->sendingQueuesRepository->findOneById($queue->id);
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $scheduledTask = $this->scheduledTasksRepository->findOneBySendingQueue($sendingQueue);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $this->tester->assertEqualDateTimes($scheduledTask->getScheduledAt(), $currentTime->addMinutes(ScheduledTaskEntity::BASIC_RESCHEDULE_TIMEOUT), 1);
  }

  public function testItDoesntRunQueueDeliveryWhenMailpoetSubscriberHasUnsubscribed() {
    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    Carbon::setTestNow($currentTime); // mock carbon to return current time
    $subscriber = $this->_createSubscriber(0, 'unsubscribed');
    $segment = $this->_createSegment();
    $this->_createSubscriberSegment($subscriber->getId(), $segment->getId());
    $newsletter = $this->_createNewsletter();

    $this->assertIsInt($segment->getId());
    $this->newsletterOptionFactory->create($newsletter, 'segment', $segment->getId());

    $queue = $this->_createQueue($newsletter->getId());
    $scheduler = $this->getScheduler();

    // return false
    $result = $scheduler->verifyMailpoetSubscriber((int)$subscriber->getId(), $newsletter, $queue);
    verify($result)->false();
    // update the time queue is scheduled to run at
    verify($this->sendingQueuesRepository->findOneById($queue->id))->null();
  }

  public function testItCanVerifyMailpoetSubscriber() {
    $subscriber = $this->_createSubscriber();
    $segment = $this->_createSegment();
    $this->_createSubscriberSegment($subscriber->getId(), $segment->getId());
    $newsletter = $this->_createNewsletter();

    $this->assertIsInt($segment->getId());
    $this->newsletterOptionFactory->create($newsletter, 'segment', $segment->getId());

    $queue = $this->_createQueue($newsletter->getId());
    $scheduler = $this->getScheduler();

    // return true after successful verification
    $result = $scheduler->verifyMailpoetSubscriber((int)$subscriber->getId(), $newsletter, $queue);
    verify($result)->true();
  }

  public function testItProcessesScheduledStandardNewsletter() {
    $subscriber = $this->_createSubscriber();
    $segment = $this->_createSegment();
    $this->_createSubscriberSegment($subscriber->getId(), $segment->getId());
    $newsletter = $this->_createNewsletter();
    $this->_createNewsletterSegment($newsletter->getId(), $segment->getId());
    $this->assertIsInt($segment->getId());
    $queue = $this->_createQueue($newsletter->getId());
    $scheduler = $this->getScheduler($this->subscribersFinder);
    $this->entityManager->refresh($newsletter);

    // return true
    verify($scheduler->processScheduledStandardNewsletter($newsletter, $queue))->true();
    // update queue's list of subscribers to process
    $sendingQueue = $this->sendingQueuesRepository->findOneById($queue->id);
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $scheduledTask = $this->scheduledTasksRepository->findOneBySendingQueue($sendingQueue);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $updatedSubscribers = $scheduledTask->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED);
    $updatedSubscribersIds = array_map(function(SubscriberEntity $subscriber): int {
      return (int)$subscriber->getId();
    }, $updatedSubscribers);
    verify($updatedSubscribersIds)->equals([$subscriber->getId()]);
    // set queue's status to null
    verify($scheduledTask->getStatus())->null();
    // set newsletter's status to sending
    $updatedNewsletter = $this->newslettersRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $updatedNewsletter);
    verify($updatedNewsletter->getStatus())->equals(NewsletterEntity::STATUS_SENDING);
  }

  public function testItFailsToProcessPostNotificationNewsletterWhenSegmentsDontExist() {
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->getId());

    // delete or reschedule queue when segments don't exist
    $scheduler = Stub::make(Scheduler::class, [
      'deleteQueueOrUpdateNextRunDate' => Expected::exactly(1, function() {
        return false;
      }),
      'loggerFactory' => $this->loggerFactory,
      'cronHelper' => $this->cronHelper,
      'newslettersRepository' => $this->newslettersRepository,
    ], $this);
    verify($scheduler->processPostNotificationNewsletter($newsletter, $queue))->false();
  }

  public function testItFailsToProcessPostNotificationNewsletterWhenSubscribersNotInSegment() {
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->getId());
    $segment = $this->_createSegment();
    $this->_createNewsletterSegment($newsletter->getId(), $segment->getId());

    // delete or reschedule queue when there are no subscribers in segments
    $scheduler = $this->construct(
      Scheduler::class,
      [
        $this->subscribersFinder,
        $this->loggerFactory,
        $this->cronHelper,
        $this->cronWorkerScheduler,
        $this->scheduledTasksRepository,
        $this->newslettersRepository,
        $this->segmentsRepository,
        $this->newsletterSegmentRepository,
        WPFunctions::get(),
        $this->security,
        $this->newsletterScheduler,
        $this->subscriberSegmentRepository,
        $this->subscribersRepository,
      ], [
      'deleteQueueOrUpdateNextRunDate' => Expected::exactly(1, function() {
        return false;
      }),
      ]);
    verify($scheduler->processPostNotificationNewsletter($newsletter, $queue))->false();
  }

  public function testItCanProcessPostNotificationNewsletter() {
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->getId());
    $segment = $this->_createSegment();
    $this->_createNewsletterSegment($newsletter->getId(), $segment->getId());
    $subscriber = $this->_createSubscriber();
    $this->_createSubscriberSegment($subscriber->getId(), $segment->getId());
    $this->assertIsInt($segment->getId());
    $this->newsletterOptionFactory->create($newsletter, 'segment', $segment->getId());
    $scheduler = $this->getScheduler($this->subscribersFinder);
    $this->entityManager->refresh($newsletter);

    // return true
    verify($scheduler->processPostNotificationNewsletter($newsletter, $queue))->true();
    // create notification history
    $notificationHistory = $this->newslettersRepository->findOneBy(['parent' => $newsletter->getId()]);
    $this->assertInstanceOf(NewsletterEntity::class, $notificationHistory);
    expect($notificationHistory)->notEmpty();
    // update queue with a list of subscribers to process and change newsletter id
    // to that of the notification history
    $sendingQueue = $this->sendingQueuesRepository->findOneById($queue->id);
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $scheduledTask = $this->scheduledTasksRepository->findOneBySendingQueue($sendingQueue);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $updatedSubscribers = $scheduledTask->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED);
    $updatedSubscribersIds = array_map(function(SubscriberEntity $subscriber): int {
      return (int)$subscriber->getId();
    }, $updatedSubscribers);
    verify($updatedSubscribersIds)->equals([$subscriber->getId()]);
    $scheduledNewsletter = $sendingQueue->getNewsletter();
    $this->assertInstanceOf(NewsletterEntity::class, $scheduledNewsletter);
    verify($scheduledNewsletter->getId())->equals($notificationHistory->getId());
    verify($sendingQueue->getCountProcessed())->equals(0);
    verify($sendingQueue->getCountToProcess())->equals(1);
    // set notification history's status to sending
    $updatedNotificationHistory = $this->newslettersRepository->findOneBy(['parent' => $newsletter->getId()]);
    $this->assertInstanceOf(NewsletterEntity::class, $updatedNotificationHistory);
    verify($updatedNotificationHistory->getStatus())->equals(NewsletterEntity::STATUS_SENDING);
  }

  public function testItFailsToProcessWhenScheduledQueuesNotFound() {
    $scheduler = $this->getScheduler();
    verify($scheduler->process())->false();
  }

  public function testItDeletesQueueDuringProcessingWhenNewsletterNotFound() {
    $queue = $this->_createQueue(1);
    $queue->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();
    $scheduler = $this->getScheduler();
    $scheduler->process();
    expect($this->sendingQueuesRepository->findAll())->count(0);
  }

  public function testItDeletesQueueDuringProcessingWhenNewsletterIsSoftDeleted() {
    $newsletter = $this->_createNewsletter();
    $newsletter->setDeletedAt(Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp')));
    $this->newslettersRepository->persist($newsletter);
    $this->newslettersRepository->flush();

    $queue = $this->_createQueue($newsletter->getId());
    $queue->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();
    $scheduler = $this->getScheduler();
    $scheduler->process();
    expect($this->sendingQueuesRepository->findAll())->count(0);
  }

  public function testItProcessesWelcomeNewsletters() {
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_WELCOME);
    $queue = $this->_createQueue($newsletter->getId());
    $queue->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();
    $scheduler = Stub::make(Scheduler::class, [
      'processWelcomeNewsletter' => Expected::exactly(1),
      'cronHelper' => $this->cronHelper,
      'scheduledTasksRepository' => $this->scheduledTasksRepository,
      'newslettersRepository' => $this->newslettersRepository,
    ], $this);
    $scheduler->process();
  }

  public function testItProcessesNotificationNewsletters() {
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->getId());
    $queue->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();
    $scheduler = Stub::make(Scheduler::class, [
      'processPostNotificationNewsletter' => Expected::exactly(1),
      'cronHelper' => $this->cronHelper,
      'scheduledTasksRepository' => $this->scheduledTasksRepository,
      'newslettersRepository' => $this->newslettersRepository,
    ], $this);
    $scheduler->process();
  }

  public function testItProcessesStandardScheduledNewsletters() {
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_STANDARD);
    $queue = $this->_createQueue($newsletter->getId());
    $queue->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();
    $scheduler = Stub::make(Scheduler::class, [
      'processScheduledStandardNewsletter' => Expected::exactly(1),
      'cronHelper' => $this->cronHelper,
      'scheduledTasksRepository' => $this->scheduledTasksRepository,
      'newslettersRepository' => $this->newslettersRepository,
    ], $this);
    $scheduler->process();
  }

  public function testItEnforcesExecutionLimitDuringProcessing() {
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->getId());
    $queue->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();
    $scheduler = Stub::make(Scheduler::class, [
      'processPostNotificationNewsletter' => Expected::exactly(1),
      'cronHelper' => $this->make(CronHelper::class, [
        'enforceExecutionLimit' => Expected::exactly(2), // call at start + during processing
      ]),
      'scheduledTasksRepository' => $this->scheduledTasksRepository,
      'newslettersRepository' => $this->newslettersRepository,
    ], $this);
    $scheduler->process();
  }

  public function testItDoesNotProcessScheduledJobsWhenNewsletterIsNotActive() {
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_STANDARD, NewsletterEntity::STATUS_DRAFT);
    $queue = $this->_createQueue($newsletter->getId());
    $queue->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();

    $scheduler = Stub::make(Scheduler::class, [
      'processScheduledStandardNewsletter' => Expected::never(),
      'cronHelper' => $this->cronHelper,
      'scheduledTasksRepository' => $this->scheduledTasksRepository,
      'newslettersRepository' => $this->newslettersRepository,
    ], $this);
    // scheduled job is not processed
    $scheduler->process();
  }

  public function testItProcessesScheduledJobsWhenNewsletterIsActive() {
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_STANDARD, NewsletterEntity::STATUS_ACTIVE);
    $queue = $this->_createQueue($newsletter->getId());
    $queue->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();

    $scheduler = Stub::make(Scheduler::class, [
      'processScheduledStandardNewsletter' => Expected::once(),
      'cronHelper' => $this->cronHelper,
      'scheduledTasksRepository' => $this->scheduledTasksRepository,
      'newslettersRepository' => $this->newslettersRepository,
    ], $this);
    // scheduled job is processed
    $scheduler->process();
  }

  public function testItDoesNotReSchedulesBounceTaskWhenSoon() {
    $task = $this->scheduledTaskFactory->create(
      'bounce',
      ScheduledTaskEntity::STATUS_SCHEDULED,
      Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->addMinutes(5)
    );

    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_STANDARD, NewsletterEntity::STATUS_DRAFT);
    $queue = $this->_createQueue($newsletter->getId());
    $scheduler = $this->getScheduler();

    $scheduler->processScheduledStandardNewsletter($newsletter, $queue);
    $refetchedTask = $this->scheduledTasksRepository->findOneById($task->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $refetchedTask);
    expect($refetchedTask->getScheduledAt())->lessThan(Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->addHours(1));
  }

  /**
   * @dataProvider dataForTestItSchedulesTransactionalEmails
   */
  public function testItSchedulesTransactionalEmails(string $subscriberStatus, bool $isExpectedToBeScheduled) {
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_AUTOMATION_TRANSACTIONAL, NewsletterEntity::STATUS_SCHEDULED);
    $subscriber = $this->_createSubscriber(0, $subscriberStatus);
    $queue = $this->_createQueue($newsletter->getId());
    $queue->setSubscribers([$subscriber->getId()]);
    $queue->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();

    $this->assertSame(SendingQueueEntity::STATUS_SCHEDULED, $queue->status);
    $this->assertSame([(string)$subscriber->getId()], $queue->getSubscribers());
    $scheduler = $this->diContainer->get(Scheduler::class);
    $scheduler->process();

    $queue = SendingTask::getByNewsletterId($newsletter->getId());
    $isExpectedToBeScheduled ?
      $this->assertSame(null, $queue->status)
      : $this->assertFalse($queue);
  }

  public function dataForTestItSchedulesTransactionalEmails(): array {
    return [
      SubscriberEntity::STATUS_INACTIVE => [SubscriberEntity::STATUS_INACTIVE, true],
      SubscriberEntity::STATUS_SUBSCRIBED => [SubscriberEntity::STATUS_SUBSCRIBED, true],
      SubscriberEntity::STATUS_BOUNCED => [SubscriberEntity::STATUS_BOUNCED, false],
      SubscriberEntity::STATUS_UNSUBSCRIBED => [SubscriberEntity::STATUS_UNSUBSCRIBED, true],
      SubscriberEntity::STATUS_UNCONFIRMED => [SubscriberEntity::STATUS_UNCONFIRMED, true],
    ];
  }

  public function testItProcessesScheduledJobsWhenNewsletterIsScheduled() {
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_STANDARD, NewsletterEntity::STATUS_SCHEDULED);
    $queue = $this->_createQueue($newsletter->getId());
    $queue->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();

    $scheduler = Stub::make(Scheduler::class, [
      'processScheduledStandardNewsletter' => Expected::once(),
      'cronHelper' => $this->cronHelper,
      'scheduledTasksRepository' => $this->scheduledTasksRepository,
      'newslettersRepository' => $this->newslettersRepository,
    ], $this);
    // scheduled job is processed
    $scheduler->process();
  }

  public function testItProcessesScheduledAutomaticEmailWhenSendingToUser() {
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_AUTOMATIC, NewsletterEntity::STATUS_SCHEDULED);
    $subscriber = $this->_createSubscriber();
    $task = SendingTask::create();
    $task->newsletterId = $newsletter->getId();
    $task->status = SendingQueueEntity::STATUS_SCHEDULED;
    $task->scheduledAt = Carbon::now()->subDay()->toDateTimeString();
    $task->setSubscribers([$subscriber->getId()]);
    $task->save();

    // scheduled task should exist
    $task = SendingTask::getByNewsletterId($newsletter->getId());
    verify($task->status)->equals(SendingQueueEntity::STATUS_SCHEDULED);
    verify($task->newsletterId)->equals($newsletter->getId());
    verify($task->getSubscribers())->equals([$subscriber->getId()]);

    // task should have its status set to null (i.e., sending)
    $scheduler = $this->getScheduler();
    $scheduler->process();
    $task = SendingTask::getByNewsletterId($newsletter->getId());
    verify($task->status)->null();
  }

  public function testItDeletesScheduledAutomaticEmailWhenUserDoesNotExist() {
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_AUTOMATIC, NewsletterEntity::STATUS_SCHEDULED);
    $subscriber = $this->_createSubscriber();
    $task = SendingTask::create();
    $task->newsletterId = $newsletter->getId();
    $task->status = SendingQueueEntity::STATUS_SCHEDULED;
    $task->scheduledAt = Carbon::now()->subDay()->toDateTimeString();
    $task->setSubscribers([$subscriber->getId()]);
    $task->save();
    $this->subscribersRepository->bulkDelete([$subscriber->getId()]);
    $this->subscribersRepository->remove($subscriber);

    // scheduled task should exist
    $task = SendingTask::getByNewsletterId($newsletter->getId());
    verify($task->status)->equals(SendingQueueEntity::STATUS_SCHEDULED);
    verify($task->newsletterId)->equals($newsletter->getId());
    verify($task->getSubscribers())->equals([$subscriber->getId()]);

    // task should be deleted
    $scheduler = $this->getScheduler();
    $scheduler->process();
    $task = SendingTask::getByNewsletterId($newsletter->getId());
    verify($task)->false();
  }

  public function testItProcessesScheduledAutomaticEmailWhenSendingToSegment() {
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_AUTOMATIC, NewsletterEntity::STATUS_SCHEDULED);
    $segment = $this->_createSegment();
    $subscriber = $this->_createSubscriber();
    $this->_createSubscriberSegment($subscriber->getId(), $segment->getId());

    $newsletterEntity = $this->entityManager->getReference(NewsletterEntity::class, $newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $this->newsletterOptionFactory->createMultipleOptions(
      $newsletterEntity,
      [
        'sendTo' => 'segment',
        'segment' => $segment->getId(),
      ]
    );

    $task = SendingTask::create();
    $task->newsletterId = $newsletter->getId();
    $task->status = SendingQueueEntity::STATUS_SCHEDULED;
    $task->scheduledAt = Carbon::now()->subDay()->toDateTimeString();
    $task->save();

    // scheduled task should exist
    $task = SendingTask::getByNewsletterId($newsletter->getId());
    verify($task->status)->equals(SendingQueueEntity::STATUS_SCHEDULED);
    verify($task->newsletterId)->equals($newsletter->getId());

    // task should have its status set to null (i.e., sending)
    $scheduler = $this->getScheduler($this->subscribersFinder);
    $scheduler->process();
    $task = SendingTask::getByNewsletterId($newsletter->getId());
    verify($task->status)->null();
    // task should have 1 subscriber added from segment
    $subscribers = $task->subscribers()->findMany();
    expect($subscribers)->count(1);
    verify($subscribers[0]->id)->equals($subscriber->getId());
  }

  public function testItProcessesScheduledAutomationEmail() {
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_AUTOMATION, NewsletterEntity::STATUS_ACTIVE);
    $subscriber = $this->_createSubscriber();
    $task = SendingTask::create();
    $task->newsletterId = $newsletter->getId();
    $task->status = SendingQueueEntity::STATUS_SCHEDULED;
    $task->scheduledAt = Carbon::now()->subDay()->toDateTimeString();
    $task->setSubscribers([$subscriber->getId()]);
    $task->save();

    // scheduled task should exist
    $task = SendingTask::getByNewsletterId($newsletter->getId());
    verify($task->status)->equals(SendingQueueEntity::STATUS_SCHEDULED);
    verify($task->newsletterId)->equals($newsletter->getId());
    verify($task->getSubscribers())->equals([$subscriber->getId()]);

    // task should have its status set to null (i.e., sending)
    $scheduler = $this->getScheduler();
    $scheduler->process();
    $task = SendingTask::getByNewsletterId($newsletter->getId());
    verify($task->status)->null();
  }

  public function testItDeletesScheduledAutomationEmailWhenUserDoesNotExist() {
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_AUTOMATION, NewsletterEntity::STATUS_ACTIVE);
    $subscriber = $this->_createSubscriber();
    $task = SendingTask::create();
    $task->newsletterId = $newsletter->getId();
    $task->status = SendingQueueEntity::STATUS_SCHEDULED;
    $task->scheduledAt = Carbon::now()->subDay()->toDateTimeString();
    $task->setSubscribers([$subscriber->getId()]);
    $task->save();
    $this->subscribersRepository->bulkDelete([$subscriber->getId()]);
    $this->subscribersRepository->remove($subscriber);

    // scheduled task should exist
    $task = SendingTask::getByNewsletterId($newsletter->getId());
    verify($task->status)->equals(SendingQueueEntity::STATUS_SCHEDULED);
    verify($task->newsletterId)->equals($newsletter->getId());
    verify($task->getSubscribers())->equals([$subscriber->getId()]);

    // task should be deleted
    $scheduler = $this->getScheduler();
    $scheduler->process();
    $task = SendingTask::getByNewsletterId($newsletter->getId());
    verify($task)->false();
  }

  public function testItUpdatesUpdateTime() {
    $originalUpdated = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->subHours(5)->toDateTimeString();
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_WELCOME, NewsletterEntity::STATUS_DRAFT);
    $queue = $this->_createQueue($newsletter->getId());
    $queue->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->updatedAt = $originalUpdated;
    $queue->save();
    $scheduler = $this->getScheduler();
    $scheduler->process();
    $newQueue = $this->scheduledTasksRepository->findOneById($queue->taskId);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $newQueue);
    expect($newQueue->getUpdatedAt())->notEquals($originalUpdated);
  }

  public function _createNewsletterSegment($newsletterId, $segmentId) {
    $newsletter = $this->entityManager->getReference(NewsletterEntity::class, $newsletterId);
    $segment = $this->entityManager->getReference(SegmentEntity::class, $segmentId);

    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $this->assertInstanceOf(SegmentEntity::class, $segment);

    $newsletterSegment = new NewsletterSegmentEntity($newsletter, $segment);

    $this->newsletterSegmentRepository->persist($newsletterSegment);
    $this->newsletterSegmentRepository->flush();

    return $newsletterSegment;
  }

  public function _createSubscriberSegment($subscriberId, $segmentId, $status = 'subscribed'): SubscriberSegmentEntity {
    $subscriber = $this->entityManager->getReference(SubscriberEntity::class, $subscriberId);
    $segment = $this->entityManager->getReference(SegmentEntity::class, $segmentId);

    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $this->assertInstanceOf(SegmentEntity::class, $segment);

    return $this->subscriberSegmentRepository->createOrUpdate($subscriber, $segment, $status);
  }

  public function _createSegment(): SegmentEntity {
    $segment = $this->segmentFactory
      ->withName('test')
      ->withType('default')
      ->create();

    return $segment;
  }

  public function _createSubscriber($wpUserId = 0, $status = 'subscribed'): SubscriberEntity {
    $subscriber = $this->subscriberFactory
      ->withEmail('john@doe.com')
      ->withFirstName('John')
      ->withLastName('Doe')
      ->withWpUserId($wpUserId)
      ->withStatus($status)
      ->create();

    return $subscriber;
  }

  public function _createNewsletter($type = NewsletterEntity::TYPE_NOTIFICATION, $status = 'active'): NewsletterEntity {
    $newsletter = $this->newsletterFactory
      ->withType($type)
      ->withStatus($status)
      ->create();

    return $newsletter;
  }

  public function _createQueue($newsletterId, $status = SendingQueueEntity::STATUS_SCHEDULED) {
    $queue = SendingTask::create();
    $queue->status = $status;
    $queue->newsletterId = $newsletterId;
    $queue->save();
    verify($queue->getErrors())->false();
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
          'user_pass' => '',
        ]
      );
    }
    $user = get_user_by('login', $username);
    $this->assertInstanceOf(WP_User::class, $user);
    wp_update_user(
      [
        'ID' => $user->ID,
        'role' => $role,
      ]
    );
    expect($user->ID)->notNull();
    return $user;
  }

  private function getScheduler(?SubscribersFinder $subscribersFinder = null): Scheduler {
    $finder = $subscribersFinder ?: $this->makeEmpty(SubscribersFinder::class);
    return $this->getServiceWithOverrides(
      Scheduler::class,
      ['subscribersFinder' => $finder]
    );
  }
}
