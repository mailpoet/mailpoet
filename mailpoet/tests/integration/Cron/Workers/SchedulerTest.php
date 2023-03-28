<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\CronWorkerScheduler;
use MailPoet\Cron\Workers\Scheduler;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Scheduler\Scheduler as NewsletterScheduler;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Newsletter\Segment\NewsletterSegmentRepository;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\SubscribersFinder;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\NewsletterOption as NewsletterOptionFactory;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
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
    $queue->status = SendingQueue::STATUS_SCHEDULED;
    $queue->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();
    expect($scheduler->getScheduledSendingTasks())->notEmpty();
  }

  public function testItCanCreateNotificationHistory() {
    $segments[] = (new SegmentFactory())
      ->withName('Segment A')
      ->create();
    $segments[] = (new SegmentFactory())
      ->withName('Segment B')
      ->create();
    $newsletter = (new NewsletterFactory())
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
    expect(strlen((string)$notificationHistory->getHash()))->equals(Security::HASH_LENGTH);
    // check the unsubscribe token of the post notification history
    expect($notificationHistory->getUnsubscribeToken())->notEquals($newsletter->getUnsubscribeToken());
    expect(strlen((string)$notificationHistory->getUnsubscribeToken()))->equals(Security::UNSUBSCRIBE_TOKEN_LENGTH);

    expect($notificationHistory->getParent())->equals($newsletter);
    expect($notificationHistory->getNewsletterSegments())->count(count($segments));
  }

  public function testItCanDeleteQueueWhenDeliveryIsSetToImmediately() {
    $newsletter = $this->_createNewsletter();
    $newsletterEntity = $this->entityManager->getReference(NewsletterEntity::class, $newsletter->id);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $this->newsletterOptionFactory->create($newsletterEntity, 'intervalType', 'immediately');

    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_WELCOME)
      ->findOne($newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $newsletter);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = $this->getScheduler();

    // queue and associated newsletter should be deleted when interval type is set to "immediately"
    expect(SendingQueue::findMany())->notEmpty();
    $scheduler->deleteQueueOrUpdateNextRunDate($queue, $newsletter);
    expect(SendingQueue::findMany())->count(0);
  }

  public function testItCanRescheduleQueueDeliveryTime() {
    $newsletter = $this->_createNewsletter();
    $this->assertInstanceOf(Newsletter::class, $newsletter);
    $newsletterEntity = $this->entityManager->getReference(NewsletterEntity::class, $newsletter->id);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $newsletterOption = $this->newsletterOptionFactory->create($newsletterEntity, 'intervalType', 'daily');

    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_WELCOME)
      ->findOne($newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $newsletter);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = $this->getScheduler();

    // queue's next run date should change when interval type is set to anything
    // other than "immediately"
    $queue = $this->_createQueue($newsletter->id);
    $newsletterOption->setValue('daily');
    $this->entityManager->persist($newsletterOption);
    $this->entityManager->flush();
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_WELCOME)->findOne($newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $newsletter);
    expect($queue->scheduledAt)->null();
    $newsletter->schedule = '0 5 * * *'; // set it to daily at 5
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
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_WELCOME);

    $newsletterEntity = $this->entityManager->getReference(NewsletterEntity::class, $newsletter->id);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $this->newsletterOptionFactory->create($newsletterEntity, 'role', 'author');

    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_WELCOME)
      ->findOne($newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $newsletter);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = $this->getScheduler();

    // return false and delete queue when subscriber is not a WP user
    $result = $scheduler->verifyWPSubscriber($subscriber->id, $newsletter, $queue);
    expect($result)->false();
    expect(SendingQueue::findMany())->count(0);
  }

  public function testItFailsWPSubscriberVerificationWhenSubscriberRoleDoesNotMatch() {
    $wPUser = $this->_createOrUpdateWPUser('editor');
    $subscriber = $this->_createSubscriber($wPUser->ID);
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_WELCOME);

    $newsletterEntity = $this->entityManager->getReference(NewsletterEntity::class, $newsletter->id);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $this->newsletterOptionFactory->create($newsletterEntity, 'role', 'author');

    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_WELCOME)
      ->findOne($newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $newsletter);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = $this->getScheduler();

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

    $newsletterEntity = $this->entityManager->getReference(NewsletterEntity::class, $newsletter->id);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $this->newsletterOptionFactory->create($newsletterEntity, 'role', 'author');

    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_WELCOME)
      ->findOne($newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $newsletter);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = $this->getScheduler();

    // return true when user exists and WP role matches the one specified for the welcome email
    $result = $scheduler->verifyWPSubscriber($subscriber->id, $newsletter, $queue);
    expect($result)->true();
    expect(count(SendingQueue::findMany()))->greaterOrEquals(1);
  }

  public function testItPassesWPSubscriberVerificationWhenSubscriberHasAnyRole() {
    $wPUser = $this->_createOrUpdateWPUser('author');
    $subscriber = $this->_createSubscriber($wPUser->ID);
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_WELCOME);

    $newsletterEntity = $this->entityManager->getReference(NewsletterEntity::class, $newsletter->id);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $this->newsletterOptionFactory->create($newsletterEntity, 'role', WelcomeScheduler::WORDPRESS_ALL_ROLES);

    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_WELCOME)
      ->findOne($newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $newsletter);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = $this->getScheduler();

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
    $scheduler = $this->getScheduler();
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
      'scheduledTasksRepository' => $this->diContainer->get(ScheduledTasksRepository::class),
    ], $this);
    expect($queue->status)->notNull();
    expect($scheduler->processWelcomeNewsletter($newsletter, $queue))->true();
    $sendingQueue = $this->sendingQueuesRepository->findOneById($queue->id);
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $scheduledTask = $this->scheduledTasksRepository->findOneBySendingQueue($sendingQueue);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    expect($scheduledTask->getStatus())->null();
  }

  public function testItProcessesWelcomeNewsletterWhenWPUserIsVerified() {
    $newsletter = $this->_createNewsletter();
    $newsletter->event = 'user';

    // return true when WP user is verified
    $queue = $this->_createQueue($newsletter->id);
    $queue->setSubscribers([1]);
    $scheduler = Stub::make(Scheduler::class, [
      'verifyWPSubscriber' => Expected::exactly(1),
      'scheduledTasksRepository' => $this->diContainer->get(ScheduledTasksRepository::class),
    ], $this);
    expect($queue->status)->notNull();
    expect($scheduler->processWelcomeNewsletter($newsletter, $queue))->true();
    // update queue's status to null
    $sendingQueue = $this->sendingQueuesRepository->findOneById($queue->id);
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $scheduledTask = $this->scheduledTasksRepository->findOneBySendingQueue($sendingQueue);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    expect($scheduledTask->getStatus())->null();
  }

  public function testItFailsMailpoetSubscriberVerificationWhenSubscriberDoesNotExist() {
    $scheduler = $this->getScheduler();
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

    $newsletterEntity = $this->entityManager->getReference(NewsletterEntity::class, $newsletter->id);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $this->newsletterOptionFactory->create($newsletterEntity, 'segment', $segment->id);

    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_NOTIFICATION)
      ->findOne($newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $newsletter);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = $this->getScheduler();

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

    $newsletterEntity = $this->entityManager->getReference(NewsletterEntity::class, $newsletter->id);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $this->newsletterOptionFactory->create($newsletterEntity, 'segment', $segment->id);

    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_NOTIFICATION)
      ->findOne($newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $newsletter);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = $this->getScheduler();

    // return false
    $result = $scheduler->verifyMailpoetSubscriber($subscriber->id, $newsletter, $queue);
    expect($result)->false();
    // update the time queue is scheduled to run at
    $sendingQueue = $this->sendingQueuesRepository->findOneById($queue->id);
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $scheduledTask = $this->scheduledTasksRepository->findOneBySendingQueue($sendingQueue);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $this->tester->assertEqualDateTimes($scheduledTask->getScheduledAt(), $currentTime->addMinutes(ScheduledTask::BASIC_RESCHEDULE_TIMEOUT), 1);
    WPFunctions::set(new WPFunctions());
  }

  public function testItDoesntRunQueueDeliveryWhenMailpoetSubscriberHasUnsubscribed() {
    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    Carbon::setTestNow($currentTime); // mock carbon to return current time
    $subscriber = $this->_createSubscriber($wpUserId = null, 'unsubscribed');
    $segment = $this->_createSegment();
    $subscriberSegment = $this->_createSubscriberSegment($subscriber->id, $segment->id);
    $newsletter = $this->_createNewsletter();
    $newsletterEntity = $this->entityManager->getReference(NewsletterEntity::class, $newsletter->id);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $this->newsletterOptionFactory->create($newsletterEntity, 'segment', $segment->id);
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_NOTIFICATION)
      ->findOne($newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $newsletter);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = $this->getScheduler();

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
    $newsletterEntity = $this->entityManager->getReference(NewsletterEntity::class, $newsletter->id);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $this->newsletterOptionFactory->create($newsletterEntity, 'segment', $segment->id);
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_NOTIFICATION)
      ->findOne($newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $newsletter);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = $this->getScheduler();

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
    $newsletterEntity = $this->entityManager->getReference(NewsletterEntity::class, $newsletter->id);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $this->newsletterOptionFactory->create($newsletterEntity, 'segment', $segment->id);
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_NOTIFICATION)
      ->findOne($newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $newsletter);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = $this->getScheduler($this->subscribersFinder);

    // return true
    expect($scheduler->processScheduledStandardNewsletter($newsletter, $queue))->true();
    // update queue's list of subscribers to process
    $sendingQueue = $this->sendingQueuesRepository->findOneById($queue->id);
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $scheduledTask = $this->scheduledTasksRepository->findOneBySendingQueue($sendingQueue);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $updatedSubscribers = $scheduledTask->getSubscribersByProcessed(ScheduledTaskSubscriber::STATUS_UNPROCESSED);
    $updatedSubscribersIds = array_map(function(SubscriberEntity $subscriber): int {
      return (int)$subscriber->getId();
    }, $updatedSubscribers);
    expect($updatedSubscribersIds)->equals([$subscriber->id]);
    // set queue's status to null
    expect($scheduledTask->getStatus())->null();
    // set newsletter's status to sending
    $updatedNewsletter = Newsletter::findOne($newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $updatedNewsletter);
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
      'newslettersRepository' => $this->newslettersRepository,
    ], $this);
    expect($scheduler->processPostNotificationNewsletter($newsletter, $queue))->false();
  }

  public function testItFailsToProcessPostNotificationNewsletterWhenSubscribersNotInSegment() {
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->id);
    $segment = $this->_createSegment();
    $newsletterSegment = $this->_createNewsletterSegment($newsletter->id, $segment->id);

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
      ], [
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
    $newsletterEntity = $this->entityManager->getReference(NewsletterEntity::class, $newsletter->id);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $this->newsletterOptionFactory->create($newsletterEntity, 'segment', $segment->id);
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_NOTIFICATION)
      ->findOne($newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $newsletter);
    $scheduler = $this->getScheduler($this->subscribersFinder);

    // return true
    expect($scheduler->processPostNotificationNewsletter($newsletter, $queue))->true();
    // create notification history
    $notificationHistory = Newsletter::where('parent_id', $newsletter->id)
      ->findOne();
    $this->assertInstanceOf(Newsletter::class, $notificationHistory);
    expect($notificationHistory)->notEmpty();
    // update queue with a list of subscribers to process and change newsletter id
    // to that of the notification history
    $sendingQueue = $this->sendingQueuesRepository->findOneById($queue->id);
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $scheduledTask = $this->scheduledTasksRepository->findOneBySendingQueue($sendingQueue);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $updatedSubscribers = $scheduledTask->getSubscribersByProcessed(ScheduledTaskSubscriber::STATUS_UNPROCESSED);
    $updatedSubscribersIds = array_map(function(SubscriberEntity $subscriber): int {
      return (int)$subscriber->getId();
    }, $updatedSubscribers);
    expect($updatedSubscribersIds)->equals([$subscriber->id]);
    $scheduledNewsletter = $sendingQueue->getNewsletter();
    $this->assertInstanceOf(NewsletterEntity::class, $scheduledNewsletter);
    expect($scheduledNewsletter->getId())->equals($notificationHistory->id);
    expect($sendingQueue->getCountProcessed())->equals(0);
    expect($sendingQueue->getCountToProcess())->equals(1);
    // set notification history's status to sending
    $updatedNotificationHistory = $this->newslettersRepository->findOneBy(['parent' => $newsletter->id]);
    $this->assertInstanceOf(NewsletterEntity::class, $updatedNotificationHistory);
    expect($updatedNotificationHistory->getStatus())->equals(Newsletter::STATUS_SENDING);
  }

  public function testItFailsToProcessWhenScheduledQueuesNotFound() {
    $scheduler = $this->getScheduler();
    expect($scheduler->process())->false();
  }

  public function testItDeletesQueueDuringProcessingWhenNewsletterNotFound() {
    $queue = $this->_createQueue(1);
    $queue->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();
    $scheduler = $this->getScheduler();
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
    $scheduler = $this->getScheduler();
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
      'scheduledTasksRepository' => $this->scheduledTasksRepository,
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
      'scheduledTasksRepository' => $this->scheduledTasksRepository,
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
      'scheduledTasksRepository' => $this->scheduledTasksRepository,
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
      'scheduledTasksRepository' => $this->scheduledTasksRepository,
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
      'scheduledTasksRepository' => $this->scheduledTasksRepository,
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
      'scheduledTasksRepository' => $this->scheduledTasksRepository,
    ], $this);
    // scheduled job is processed
    $scheduler->process();
  }

  public function testItDoesNotReSchedulesBounceTaskWhenSoon() {
    $task = ScheduledTask::createOrUpdate([
      'type' => 'bounce',
      'status' => ScheduledTask::STATUS_SCHEDULED,
      'scheduled_at' => Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->addMinutes(5),
    ]);
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_STANDARD, Newsletter::STATUS_DRAFT);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = $this->getScheduler();

    $scheduler->processScheduledStandardNewsletter($newsletter, $queue);
    $refetchedTask = ScheduledTask::where('id', $task->id)->findOne();
    $this->assertInstanceOf(ScheduledTask::class, $refetchedTask);
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
      'scheduledTasksRepository' => $this->scheduledTasksRepository,
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
    $task->scheduledAt = Carbon::now()->subDay()->toDateTimeString();
    $task->setSubscribers([$subscriber->id]);
    $task->save();

    // scheduled task should exist
    $task = SendingTask::getByNewsletterId($newsletter->id);
    expect($task->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect($task->newsletterId)->equals($newsletter->id);
    expect($task->getSubscribers())->equals([$subscriber->id]);

    // task should have its status set to null (i.e., sending)
    $scheduler = $this->getScheduler();
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
    $task->scheduledAt = Carbon::now()->subDay()->toDateTimeString();
    $task->setSubscribers([$subscriber->id]);
    $task->save();
    $subscriber->delete();

    // scheduled task should exist
    $task = SendingTask::getByNewsletterId($newsletter->id);
    expect($task->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect($task->newsletterId)->equals($newsletter->id);
    expect($task->getSubscribers())->equals([$subscriber->id]);

    // task should be deleted
    $scheduler = $this->getScheduler();
    $scheduler->process();
    $task = SendingTask::getByNewsletterId($newsletter->id);
    expect($task)->false();
  }

  public function testItProcessesScheduledAutomaticEmailWhenSendingToSegment() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_AUTOMATIC, Newsletter::STATUS_SCHEDULED);
    $segment = $this->_createSegment();
    $subscriber = $this->_createSubscriber();
    $segmentSubscriber = $this->_createSubscriberSegment($subscriber->id, $segment->id);

    $newsletterEntity = $this->entityManager->getReference(NewsletterEntity::class, $newsletter->id);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $this->newsletterOptionFactory->createMultipleOptions(
      $newsletterEntity,
      [
        'sendTo' => 'segment',
        'segment' => $segment->id,
      ]
    );

    $task = SendingTask::create();
    $task->newsletterId = $newsletter->id;
    $task->status = SendingQueue::STATUS_SCHEDULED;
    $task->scheduledAt = Carbon::now()->subDay()->toDateTimeString();
    $task->save();

    // scheduled task should exist
    $task = SendingTask::getByNewsletterId($newsletter->id);
    expect($task->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect($task->newsletterId)->equals($newsletter->id);

    // task should have its status set to null (i.e., sending)
    $scheduler = $this->getScheduler($this->subscribersFinder);
    $scheduler->process();
    $task = SendingTask::getByNewsletterId($newsletter->id);
    expect($task->status)->null();
    // task should have 1 subscriber added from segment
    $subscribers = $task->subscribers()->findMany();
    expect($subscribers)->count(1);
    expect($subscribers[0]->id)->equals($subscriber->id);
  }

  public function testItProcessesScheduledAutomationEmail() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_AUTOMATION, Newsletter::STATUS_ACTIVE);
    $subscriber = $this->_createSubscriber();
    $task = SendingTask::create();
    $task->newsletterId = $newsletter->id;
    $task->status = SendingQueue::STATUS_SCHEDULED;
    $task->scheduledAt = Carbon::now()->subDay()->toDateTimeString();
    $task->setSubscribers([$subscriber->id]);
    $task->save();

    // scheduled task should exist
    $task = SendingTask::getByNewsletterId($newsletter->id);
    expect($task->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect($task->newsletterId)->equals($newsletter->id);
    expect($task->getSubscribers())->equals([$subscriber->id]);

    // task should have its status set to null (i.e., sending)
    $scheduler = $this->getScheduler();
    $scheduler->process();
    $task = SendingTask::getByNewsletterId($newsletter->id);
    expect($task->status)->null();
  }

  public function testItDeletesScheduledAutomationEmailWhenUserDoesNotExist() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_AUTOMATION, Newsletter::STATUS_ACTIVE);
    $subscriber = $this->_createSubscriber();
    $task = SendingTask::create();
    $task->newsletterId = $newsletter->id;
    $task->status = SendingQueue::STATUS_SCHEDULED;
    $task->scheduledAt = Carbon::now()->subDay()->toDateTimeString();
    $task->setSubscribers([$subscriber->id]);
    $task->save();
    $subscriber->delete();

    // scheduled task should exist
    $task = SendingTask::getByNewsletterId($newsletter->id);
    expect($task->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect($task->newsletterId)->equals($newsletter->id);
    expect($task->getSubscribers())->equals([$subscriber->id]);

    // task should be deleted
    $scheduler = $this->getScheduler();
    $scheduler->process();
    $task = SendingTask::getByNewsletterId($newsletter->id);
    expect($task)->false();
  }

  public function testItUpdatesUpdateTime() {
    $originalUpdated = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->subHours(5)->toDateTimeString();
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_WELCOME, Newsletter::STATUS_DRAFT);
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->updatedAt = $originalUpdated;
    $queue->save();
    $scheduler = $this->getScheduler();
    $scheduler->process();
    $newQueue = ScheduledTask::findOne($queue->taskId);
    $this->assertInstanceOf(ScheduledTask::class, $newQueue);
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

  public function _after() {
    Carbon::setTestNow();
  }
}
