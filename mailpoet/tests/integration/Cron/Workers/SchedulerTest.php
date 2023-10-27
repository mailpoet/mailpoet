<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\Scheduler;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
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
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Newsletter\Segment\NewsletterSegmentRepository;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Segments\SubscribersFinder;
use MailPoet\Subscribers\SubscriberSegmentRepository;
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

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var NewsletterOptionFactory */
  private $newsletterOptionFactory;

  /** @var NewsletterSegmentRepository */
  private $newsletterSegmentRepository;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  /** @var SubscriberFactory */
  private $subscriberFactory;

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
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->newsletterOptionFactory = new NewsletterOptionFactory();
    $this->newsletterSegmentRepository = $this->diContainer->get(NewsletterSegmentRepository::class);
    $this->sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);
    $this->subscriberSegmentRepository = $this->diContainer->get(SubscriberSegmentRepository::class);
    $this->subscriberFactory = new SubscriberFactory();
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
      verify($e->getMessage())->stringStartsWith('The maximum execution time');
    }
  }

  public function testItCanGetScheduledQueues() {
    $newsletter = $this->_createNewsletter();
    $scheduler = $this->diContainer->get(Scheduler::class);
    verify($scheduler->getScheduledSendingTasks())->empty();
    $this->createTaskWithQueue($newsletter);
    verify($scheduler->getScheduledSendingTasks())->notEmpty();
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
    verify($notificationHistory)->empty();

    // create notification history and ensure that it exists
    $scheduler = $this->getScheduler();
    $scheduler->createPostNotificationHistory($newsletter);
    $notificationHistory = $this->newslettersRepository->findOneBy(['parent' => $newsletter, 'type' => NewsletterEntity::TYPE_NOTIFICATION_HISTORY]);
    verify($notificationHistory)->notEmpty();
    $this->assertInstanceOf(NewsletterEntity::class, $notificationHistory);
    // check the hash of the post notification history
    verify($notificationHistory->getHash())->notEquals($newsletter->getHash());
    verify(strlen((string)$notificationHistory->getHash()))->equals(Security::HASH_LENGTH);
    // check the unsubscribe token of the post notification history
    verify($notificationHistory->getUnsubscribeToken())->notEquals($newsletter->getUnsubscribeToken());
    verify(strlen((string)$notificationHistory->getUnsubscribeToken()))->equals(Security::UNSUBSCRIBE_TOKEN_LENGTH);

    verify($notificationHistory->getParent())->equals($newsletter);
    verify($notificationHistory->getNewsletterSegments())->arrayCount(count($segments));
  }

  public function testItCanDeleteQueueWhenDeliveryIsSetToImmediately() {
    $newsletter = $this->_createNewsletter();
    $this->newsletterOptionFactory->create($newsletter, 'intervalType', 'immediately');
    $task = $this->createTaskWithQueue($newsletter);

    // task and queue should be deleted when interval type is set to "immediately"
    $scheduler = $this->getScheduler();
    verify($this->sendingQueuesRepository->findAll())->arrayCount(1);
    verify($this->scheduledTasksRepository->findAll())->arrayCount(1);
    $scheduler->deleteQueueOrUpdateNextRunDate($task, $newsletter);
    verify($this->sendingQueuesRepository->findAll())->arrayCount(0);
    verify($this->scheduledTasksRepository->findAll())->arrayCount(0);
  }

  public function testItCanRescheduleQueueDeliveryTime() {
    $newsletter = $this->_createNewsletter();
    $task = $this->createTaskWithQueue($newsletter);
    $task->setScheduledAt(null);
    $this->newsletterOptionFactory->create($newsletter, 'intervalType', 'daily');
    $this->newsletterOptionFactory->create($newsletter, NewsletterOptionFieldEntity::NAME_SCHEDULE, '0 5 * * *');
    $this->entityManager->flush();

    verify($task->getScheduledAt())->null();

    // queue's next run date should change when interval type is set to anything other than "immediately"
    $scheduler = $this->getScheduler();
    $scheduler->deleteQueueOrUpdateNextRunDate($task, $newsletter);

    $queue = $task->getSendingQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $task = $this->scheduledTasksRepository->findOneBySendingQueue($queue);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    verify($task->getScheduledAt())->notNull();
  }

  public function testItFailsWPSubscriberVerificationWhenSubscriberIsNotAWPUser() {
    $this->_createOrUpdateWPUser('author');
    $subscriber = $this->_createSubscriber(); // do not pass WP user ID
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_WELCOME);

    $this->newsletterOptionFactory->create($newsletter, 'role', 'author');

    $task = $this->createTaskWithQueue($newsletter);
    $scheduler = $this->getScheduler();

    // return false and delete queue when subscriber is not a WP user
    $result = $scheduler->verifyWPSubscriber((int)$subscriber->getId(), $newsletter, $task);
    verify($result)->false();
    verify($this->sendingQueuesRepository->findAll())->arrayCount(0);
  }

  public function testItFailsWPSubscriberVerificationWhenSubscriberRoleDoesNotMatch() {
    $wPUser = $this->_createOrUpdateWPUser('editor');
    $subscriber = $this->_createSubscriber($wPUser->ID);
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_WELCOME);

    $this->newsletterOptionFactory->create($newsletter, 'role', 'author');

    $task = $this->createTaskWithQueue($newsletter);
    $scheduler = $this->getScheduler();

    // return false and delete queue when subscriber role is different from the one
    // specified for the welcome email
    $result = $scheduler->verifyWPSubscriber((int)$subscriber->getId(), $newsletter, $task);
    verify($result)->false();
    verify($this->sendingQueuesRepository->findAll())->arrayCount(0);
  }

  public function testItPassesWPSubscriberVerificationWhenSubscriberExistsAndRoleMatches() {
    $wPUser = $this->_createOrUpdateWPUser('author');
    $subscriber = $this->_createSubscriber($wPUser->ID);
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_WELCOME);

    $this->newsletterOptionFactory->create($newsletter, 'role', 'author');

    $task = $this->createTaskWithQueue($newsletter);
    $scheduler = $this->getScheduler();

    // return true when user exists and WP role matches the one specified for the welcome email
    $result = $scheduler->verifyWPSubscriber((int)$subscriber->getId(), $newsletter, $task);
    verify($result)->true();
    verify(count($this->sendingQueuesRepository->findAll()))->greaterThanOrEqual(1);
  }

  public function testItPassesWPSubscriberVerificationWhenSubscriberHasAnyRole() {
    $wPUser = $this->_createOrUpdateWPUser('author');
    $subscriber = $this->_createSubscriber($wPUser->ID);
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_WELCOME);

    $this->newsletterOptionFactory->create($newsletter, 'role', WelcomeScheduler::WORDPRESS_ALL_ROLES);

    $task = $this->createTaskWithQueue($newsletter);
    $scheduler = $this->getScheduler();

    // true when user exists and has any role
    $result = $scheduler->verifyWPSubscriber((int)$subscriber->getId(), $newsletter, $task);
    verify($result)->true();
    verify(count($this->sendingQueuesRepository->findAll()))->greaterThanOrEqual(1);
  }

  public function testItDoesNotProcessWelcomeNewsletterWhenThereAreNoSubscribersToProcess() {
    $newsletter = $this->_createNewsletter();
    $task = $this->createTaskWithQueue($newsletter);

    // delete task and queue when the list of subscribers to process is blank
    $scheduler = $this->getScheduler();
    $result = $scheduler->processWelcomeNewsletter($newsletter, $task);
    verify($result)->false();
    verify($this->scheduledTasksRepository->findAll())->arrayCount(0);
    verify($this->sendingQueuesRepository->findAll())->arrayCount(0);
  }

  public function testItDoesNotProcessWelcomeNewsletterWhenWPUserCannotBeVerified() {
    $newsletter = $this->_createNewsletter();
    $subscriber = $this->_createSubscriber();
    $this->newsletterOptionFactory->create($newsletter, 'event', 'user');
    $task = $this->createTaskWithQueue($newsletter);
    $this->createTaskSubscriber($task, $subscriber);

    // return false when WP user cannot be verified
    $scheduler = Stub::make(Scheduler::class, [
      'verifyWPSubscriber' => Expected::exactly(1, function() {
        return false;
      }),
    ], $this);
    verify($scheduler->processWelcomeNewsletter($newsletter, $task))->false();
  }

  public function testItDoesNotProcessWelcomeNewsletterWhenSubscriberCannotBeVerified() {
    $newsletter = $this->_createNewsletter();
    $subscriber = $this->_createSubscriber();
    $this->newsletterOptionFactory->create($newsletter, 'event', 'segment');
    $task = $this->createTaskWithQueue($newsletter);
    $this->createTaskSubscriber($task, $subscriber);

    // return false when subscriber cannot be verified
    $scheduler = Stub::make(Scheduler::class, [
      'verifyMailpoetSubscriber' => Expected::exactly(1, function() {
        return false;
      }),
    ], $this);
    verify($scheduler->processWelcomeNewsletter($newsletter, $task))->false();
  }

  public function testItProcessesWelcomeNewsletterWhenSubscriberIsVerified() {
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_WELCOME);
    $subscriber = $this->_createSubscriber();
    $this->newsletterOptionFactory->create($newsletter, 'event', 'segment');

    // return true when subscriber is verified and update the task status to null
    $task = $this->createTaskWithQueue($newsletter);
    $this->createTaskSubscriber($task, $subscriber);
    $scheduler = Stub::make(Scheduler::class, [
      'verifyMailpoetSubscriber' => Expected::exactly(1, true),
      'scheduledTasksRepository' => $this->diContainer->get(ScheduledTasksRepository::class),
    ], $this);
    verify($task->getStatus())->notNull();
    verify($scheduler->processWelcomeNewsletter($newsletter, $task))->true();
    $sendingQueue = $task->getSendingQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $scheduledTask = $this->scheduledTasksRepository->findOneBySendingQueue($sendingQueue);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    verify($scheduledTask->getStatus())->null();
  }

  public function testItProcessesWelcomeNewsletterWhenWPUserIsVerified() {
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_WELCOME);
    $subscriber = $this->_createSubscriber();
    $this->newsletterOptionFactory->create($newsletter, 'event', 'user');

    // return true when WP user is verified
    $task = $this->createTaskWithQueue($newsletter);
    $this->createTaskSubscriber($task, $subscriber);
    $scheduler = Stub::make(Scheduler::class, [
      'verifyWPSubscriber' => Expected::exactly(1, true),
      'scheduledTasksRepository' => $this->diContainer->get(ScheduledTasksRepository::class),
    ], $this);
    verify($task->getStatus())->notNull();
    verify($scheduler->processWelcomeNewsletter($newsletter, $task))->true();
    // update task status to null
    $sendingQueue = $task->getSendingQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $scheduledTask = $this->scheduledTasksRepository->findOneBySendingQueue($sendingQueue);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    verify($scheduledTask->getStatus())->null();
  }

  public function testItFailsMailpoetSubscriberVerificationWhenSubscriberDoesNotExist() {
    $scheduler = $this->getScheduler();
    $newsletter = $this->_createNewsletter();
    $task = $this->createTaskWithQueue($newsletter);

    // return false
    $result = $scheduler->verifyMailpoetSubscriber(PHP_INT_MAX, $newsletter, $task);
    verify($result)->false();
    // delete task and queue when subscriber can't be found
    verify($this->scheduledTasksRepository->findAll())->arrayCount(0);
    verify($this->sendingQueuesRepository->findAll())->arrayCount(0);
  }

  public function testItFailsMailpoetSubscriberVerificationWhenSubscriberIsNotInSegment() {
    $subscriber = $this->_createSubscriber();
    $segment = $this->_createSegment();
    $newsletter = $this->_createNewsletter();

    $this->assertIsInt($segment->getId());
    $this->newsletterOptionFactory->create($newsletter, 'segment', $segment->getId());

    $task = $this->createTaskWithQueue($newsletter);
    $scheduler = $this->getScheduler();

    // return false
    $result = $scheduler->verifyMailpoetSubscriber((int)$subscriber->getId(), $newsletter, $task);
    verify($result)->false();
    // delete task and queue when subscriber is not in segment specified for the newsletter
    verify($this->scheduledTasksRepository->findAll())->arrayCount(0);
    verify($this->sendingQueuesRepository->findAll())->arrayCount(0);
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

    $task = $this->createTaskWithQueue($newsletter);
    $scheduler = $this->getScheduler();

    // return false
    $result = $scheduler->verifyMailpoetSubscriber((int)$subscriber->getId(), $newsletter, $task);
    verify($result)->false();
    // update the time queue is scheduled to run at
    $sendingQueue = $task->getSendingQueue();
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

    $task = $this->createTaskWithQueue($newsletter);
    $scheduler = $this->getScheduler();

    $queue = $task->getSendingQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $taskId = $task->getId();
    $queueId = $queue->getId();

    // return false
    $result = $scheduler->verifyMailpoetSubscriber((int)$subscriber->getId(), $newsletter, $task);
    verify($result)->false();
    // update the time queue is scheduled to run at
    verify($this->scheduledTasksRepository->findOneById($taskId))->null();
    verify($this->sendingQueuesRepository->findOneById($queueId))->null();
  }

  public function testItCanVerifyMailpoetSubscriber() {
    $subscriber = $this->_createSubscriber();
    $segment = $this->_createSegment();
    $this->_createSubscriberSegment($subscriber->getId(), $segment->getId());
    $newsletter = $this->_createNewsletter();

    $this->assertIsInt($segment->getId());
    $this->newsletterOptionFactory->create($newsletter, 'segment', $segment->getId());

    $task = $this->createTaskWithQueue($newsletter);
    $scheduler = $this->getScheduler();

    // return true after successful verification
    $result = $scheduler->verifyMailpoetSubscriber((int)$subscriber->getId(), $newsletter, $task);
    verify($result)->true();
  }

  public function testItProcessesScheduledStandardNewsletter() {
    $subscriber = $this->_createSubscriber();
    $segment = $this->_createSegment();
    $this->_createSubscriberSegment($subscriber->getId(), $segment->getId());
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_STANDARD);
    $this->_createNewsletterSegment($newsletter->getId(), $segment->getId());
    $this->assertIsInt($segment->getId());
    $task = $this->createTaskWithQueue($newsletter);
    $scheduler = $this->getScheduler($this->subscribersFinder);
    $this->entityManager->refresh($newsletter);

    // return true
    verify($scheduler->processScheduledStandardNewsletter($newsletter, $task))->true();
    // update queue's list of subscribers to process
    $updatedSubscribers = $task->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED);
    $updatedSubscribersIds = array_map(function(SubscriberEntity $subscriber): int {
      return (int)$subscriber->getId();
    }, $updatedSubscribers);
    verify($updatedSubscribersIds)->equals([$subscriber->getId()]);
    // set queue's status to null
    verify($task->getStatus())->null();
    // set newsletter's status to sending
    $updatedNewsletter = $this->newslettersRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $updatedNewsletter);
    verify($updatedNewsletter->getStatus())->equals(NewsletterEntity::STATUS_SENDING);
  }

  public function testItFailsToProcessPostNotificationNewsletterWhenSegmentsDontExist() {
    $newsletter = $this->_createNewsletter();
    $task = $this->createTaskWithQueue($newsletter);

    // delete or reschedule queue when segments don't exist
    $scheduler = Stub::make(Scheduler::class, [
      'deleteQueueOrUpdateNextRunDate' => Expected::exactly(1, function() {
        return false;
      }),
      'loggerFactory' => $this->loggerFactory,
      'cronHelper' => $this->cronHelper,
      'newslettersRepository' => $this->newslettersRepository,
    ], $this);
    verify($scheduler->processPostNotificationNewsletter($newsletter, $task))->false();
  }

  public function testItFailsToProcessPostNotificationNewsletterWhenSubscribersNotInSegment() {
    $newsletter = $this->_createNewsletter();
    $task = $this->createTaskWithQueue($newsletter);
    $segment = $this->_createSegment();
    $this->_createNewsletterSegment($newsletter->getId(), $segment->getId());
    $this->entityManager->refresh($newsletter);

    $scheduler = $this->getScheduler();
    verify($scheduler->processPostNotificationNewsletter($newsletter, $task))->false();
  }

  public function testItCanProcessPostNotificationNewsletter() {
    $newsletter = $this->_createNewsletter();
    $task = $this->createTaskWithQueue($newsletter);
    $segment = $this->_createSegment();
    $this->_createNewsletterSegment($newsletter->getId(), $segment->getId());
    $subscriber = $this->_createSubscriber();
    $this->_createSubscriberSegment($subscriber->getId(), $segment->getId());
    $this->assertIsInt($segment->getId());
    $this->newsletterOptionFactory->create($newsletter, 'segment', $segment->getId());
    $scheduler = $this->getScheduler($this->subscribersFinder);
    $this->entityManager->refresh($newsletter);

    // return true
    verify($scheduler->processPostNotificationNewsletter($newsletter, $task))->true();
    // create notification history
    $notificationHistory = $this->newslettersRepository->findOneBy(['parent' => $newsletter->getId()]);
    $this->assertInstanceOf(NewsletterEntity::class, $notificationHistory);
    verify($notificationHistory)->notEmpty();
    // update queue with a list of subscribers to process and change newsletter id
    // to that of the notification history
    $sendingQueue = $task->getSendingQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $updatedSubscribers = $task->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED);
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

  public function testItDeletesQueueDuringProcessingWhenNewsletterNotFound() {
    $subscriber = $this->_createSubscriber();
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_STANDARD);
    $task = $this->createTaskWithQueue($newsletter);
    $this->createTaskSubscriber($task, $subscriber);

    verify($this->sendingQueuesRepository->findAll())->arrayCount(1);
    verify($this->scheduledTasksRepository->findOneByNewsletter($newsletter))->notNull();

    // remove newsletter, but not any related data
    $this->entityManager->getConnection()->delete(
      $this->entityManager->getClassMetadata(NewsletterEntity::class)->getTableName(),
      ['id' => $newsletter->getId()]
    );
    $this->entityManager->clear();

    $scheduler = $this->getScheduler();
    $scheduler->process();
    verify($this->sendingQueuesRepository->findAll())->arrayCount(0);
    verify($this->scheduledTasksRepository->findOneByNewsletter($newsletter))->null();
  }

  public function testItDeletesQueueDuringProcessingWhenNewsletterIsSoftDeleted() {
    $newsletter = $this->_createNewsletter();
    $newsletter->setDeletedAt(Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp')));
    $this->createTaskWithQueue($newsletter);
    $this->newslettersRepository->flush();

    $scheduler = $this->getScheduler();
    $scheduler->process();
    verify($this->scheduledTasksRepository->findAll())->arrayCount(0);
    verify($this->sendingQueuesRepository->findAll())->arrayCount(0);
  }

  public function testItProcessesWelcomeNewsletters() {
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_WELCOME);
    $task = $this->createTaskWithQueue($newsletter);
    $task->setScheduledAt(Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp')));
    $this->entityManager->flush();
    $scheduler = $this->getSchedulerMock([
      'processWelcomeNewsletter' => Expected::exactly(1),
    ]);
    $scheduler->process();
  }

  public function testItProcessesNotificationNewsletters() {
    $newsletter = $this->_createNewsletter();
    $task = $this->createTaskWithQueue($newsletter);
    $task->setScheduledAt(Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp')));
    $this->entityManager->flush();
    $scheduler = $this->getSchedulerMock([
      'processPostNotificationNewsletter' => Expected::exactly(1),
    ]);
    $scheduler->process();
  }

  public function testItProcessesStandardScheduledNewsletters() {
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_STANDARD);
    $task = $this->createTaskWithQueue($newsletter);
    $task->setScheduledAt(Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp')));
    $this->entityManager->flush();
    $scheduler = $this->getSchedulerMock([
      'processScheduledStandardNewsletter' => Expected::exactly(1),
    ]);
    $scheduler->process();
  }

  public function testItEnforcesExecutionLimitDuringProcessing() {
    $newsletter = $this->_createNewsletter();
    $task = $this->createTaskWithQueue($newsletter);
    $task->setScheduledAt(Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp')));
    $this->entityManager->flush();
    $scheduler = $this->getSchedulerMock([
      'processPostNotificationNewsletter' => Expected::exactly(1),
      'cronHelper' => $this->make(CronHelper::class, [
        'enforceExecutionLimit' => Expected::exactly(2), // call at start + during processing
      ]),
    ]);
    $scheduler->process();
  }

  public function testItDoesNotProcessScheduledJobsWhenNewsletterIsNotActive() {
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_STANDARD, NewsletterEntity::STATUS_DRAFT);
    $task = $this->createTaskWithQueue($newsletter);
    $task->setScheduledAt(Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp')));
    $this->entityManager->flush();
    $scheduler = $this->getSchedulerMock([
      'processScheduledStandardNewsletter' => Expected::never(),
    ]);
    // scheduled job is not processed
    $scheduler->process();
  }

  public function testItProcessesScheduledJobsWhenNewsletterIsActive() {
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_STANDARD, NewsletterEntity::STATUS_ACTIVE);
    $task = $this->createTaskWithQueue($newsletter);
    $task->setScheduledAt(Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp')));
    $this->entityManager->flush();
    $scheduler = $this->getSchedulerMock([
      'processScheduledStandardNewsletter' => Expected::once(),
    ]);
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
    $task = $this->createTaskWithQueue($newsletter);
    $scheduler = $this->getScheduler();

    $scheduler->processScheduledStandardNewsletter($newsletter, $task);
    $refetchedTask = $this->scheduledTasksRepository->findOneById($task->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $refetchedTask);
    verify($refetchedTask->getScheduledAt())->lessThan(Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->addHours(1));
  }

  /**
   * @dataProvider dataForTestItSchedulesTransactionalEmails
   */
  public function testItSchedulesTransactionalEmails(string $subscriberStatus, bool $isExpectedToBeScheduled) {
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_AUTOMATION_TRANSACTIONAL, NewsletterEntity::STATUS_SCHEDULED);
    $subscriber = $this->_createSubscriber(0, $subscriberStatus);
    $task = $this->createTaskWithQueue($newsletter);
    $task->setScheduledAt(Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp')));
    $this->createTaskSubscriber($task, $subscriber);
    $this->entityManager->flush();

    $scheduler = $this->diContainer->get(Scheduler::class);
    $scheduler->process();

    $task = $this->scheduledTasksRepository->findOneByNewsletter($newsletter);
    if ($isExpectedToBeScheduled) {
      $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
      $this->assertSame(null, $task->getStatus());
    } else {
      $this->assertNull($task);
    }
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
    $task = $this->createTaskWithQueue($newsletter);
    $task->setScheduledAt(Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp')));
    $this->entityManager->flush();

    $scheduler = $this->getSchedulerMock([
      'processScheduledStandardNewsletter' => Expected::once(),
    ]);
    // scheduled job is processed
    $scheduler->process();
  }

  public function testItProcessesScheduledAutomaticEmailWhenSendingToUser() {
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_AUTOMATIC, NewsletterEntity::STATUS_SCHEDULED);
    $subscriber = $this->_createSubscriber();
    $task = $this->createTaskWithQueue($newsletter);
    $this->createTaskSubscriber($task, $subscriber);

    // task should have its status set to null (i.e., sending)
    $scheduler = $this->getScheduler();
    $scheduler->process();
    $task = $this->scheduledTasksRepository->findOneByNewsletter($newsletter);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    verify($task->getStatus())->null();
  }

  public function testItDeletesScheduledAutomaticEmailWhenUserDoesNotExist() {
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_AUTOMATIC, NewsletterEntity::STATUS_SCHEDULED);
    $subscriber = $this->_createSubscriber();
    $task = $this->createTaskWithQueue($newsletter);
    $this->createTaskSubscriber($task, $subscriber);

    verify($this->scheduledTasksRepository->findOneByNewsletter($newsletter))->notNull();

    // remove subscriber, but not scheduled task subscriber
    $this->entityManager->getConnection()->delete(
      $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName(),
      ['id' => $subscriber->getId()]
    );
    $this->entityManager->clear();

    // task should be deleted
    $scheduler = $this->getScheduler();
    $scheduler->process();
    verify($this->scheduledTasksRepository->findOneByNewsletter($newsletter))->null();
  }

  public function testItProcessesScheduledAutomaticEmailWhenSendingToSegment() {
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_AUTOMATIC, NewsletterEntity::STATUS_SCHEDULED);
    $segment = $this->_createSegment();
    $subscriber = $this->_createSubscriber();
    $this->_createSubscriberSegment($subscriber->getId(), $segment->getId());
    $this->newsletterOptionFactory->createMultipleOptions(
      $newsletter,
      [
        'sendTo' => 'segment',
        'segment' => $segment->getId(),
      ]
    );

    $task = $this->createTaskWithQueue($newsletter);
    $task->setScheduledAt(Carbon::now()->subDay());
    $this->entityManager->flush();

    // task should have its status set to null (i.e., sending)
    $scheduler = $this->getScheduler($this->subscribersFinder);
    $scheduler->process();

    $task = $this->scheduledTasksRepository->findOneByNewsletter($newsletter);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    verify($task->getStatus())->null();
    // task should have 1 subscriber added from segment
    $subscribers = $task->getSubscribers();
    $this->assertCount(1, $subscribers);
    $this->assertInstanceOf(ScheduledTaskSubscriberEntity::class, $subscribers[0]);
    $this->assertSame($subscriber, $subscribers[0]->getSubscriber());
  }

  public function testItProcessesScheduledAutomationEmail() {
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_AUTOMATION, NewsletterEntity::STATUS_ACTIVE);
    $subscriber = $this->_createSubscriber();
    $task = $this->createTaskWithQueue($newsletter);
    $this->createTaskSubscriber($task, $subscriber);

    // task should have its status set to null (i.e., sending)
    $scheduler = $this->getScheduler();
    $scheduler->process();
    $task = $this->scheduledTasksRepository->findOneByNewsletter($newsletter);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    verify($task->getStatus())->null();
  }

  public function testItDeletesScheduledAutomationEmailWhenUserDoesNotExist() {
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_AUTOMATION, NewsletterEntity::STATUS_ACTIVE);
    $subscriber = $this->_createSubscriber();
    $task = $this->createTaskWithQueue($newsletter);
    $this->createTaskSubscriber($task, $subscriber);

    $task = $this->scheduledTasksRepository->findOneByNewsletter($newsletter);
    verify($task)->notNull();

    // remove subscriber, but not scheduled task subscriber
    $this->entityManager->getConnection()->delete(
      $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName(),
      ['id' => $subscriber->getId()]
    );
    $this->entityManager->clear();

    // task should be deleted
    $scheduler = $this->getScheduler();
    $scheduler->process();
    $task = $this->scheduledTasksRepository->findOneByNewsletter($newsletter);
    verify($task)->null();
  }

  public function testItUpdatesUpdateTime() {
    $originalUpdated = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->subHours(5);
    $newsletter = $this->_createNewsletter(NewsletterEntity::TYPE_WELCOME, NewsletterEntity::STATUS_DRAFT);
    $task = $this->createTaskWithQueue($newsletter);
    $task->setScheduledAt(Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp')));
    $task->setUpdatedAt($originalUpdated);
    $this->entityManager->flush();
    $scheduler = $this->getScheduler();
    $scheduler->process();
    $newTask = $this->scheduledTasksRepository->findOneById($task->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $newTask);
    verify($newTask->getUpdatedAt())->notEquals($originalUpdated);
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
    verify($user->ID)->notNull();
    return $user;
  }

  private function createTaskWithQueue(NewsletterEntity $newsletter): ScheduledTaskEntity {
    $task = new ScheduledTaskEntity();
    $queue = new SendingQueueEntity();
    $task->setSendingQueue($queue);
    $queue->setTask($task);

    $task->setType(SendingQueue::TASK_TYPE);
    $task->setStatus(SendingQueueEntity::STATUS_SCHEDULED);
    $task->setScheduledAt(Carbon::now()->subDay());
    $queue->setNewsletter($newsletter);

    $this->entityManager->persist($task);
    $this->entityManager->persist($queue);
    $this->entityManager->flush();
    return $task;
  }

  private function createTaskSubscriber(ScheduledTaskEntity $task, SubscriberEntity $subscriber): ScheduledTaskSubscriberEntity {
    $scheduledTaskSubscriber = new ScheduledTaskSubscriberEntity($task, $subscriber);
    $this->entityManager->persist($scheduledTaskSubscriber);
    $task->getSubscribers()->add($scheduledTaskSubscriber);
    if ($task->getSendingQueue()) {
      $task->getSendingQueue()->setSubscribers((string)$subscriber->getId());
    }
    $this->entityManager->flush();
    return $scheduledTaskSubscriber;
  }

  private function getSchedulerMock(array $mocks): Scheduler {
    return $this->make(Scheduler::class, $mocks + [
      'cronHelper' => $this->cronHelper,
      'scheduledTasksRepository' => $this->scheduledTasksRepository,
      'sendingQueuesRepository' => $this->sendingQueuesRepository,
      'newslettersRepository' => $this->newslettersRepository,
    ]);
  }

  private function getScheduler(?SubscribersFinder $subscribersFinder = null): Scheduler {
    $finder = $subscribersFinder ?: $this->makeEmpty(SubscribersFinder::class);
    return $this->getServiceWithOverrides(
      Scheduler::class,
      ['subscribersFinder' => $finder]
    );
  }
}
