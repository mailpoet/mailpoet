<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\NewsletterOption;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\SendingQueue as SendingQueueFactory;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class WelcomeTest extends \MailPoetTest {

  /** @var SegmentsRepository */
  private $segmentRepository;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var WelcomeScheduler */
  private $welcomeScheduler;

  /** @var SubscriberEntity */
  private $subscriber;

  /** @var SegmentEntity */
  private $segment;

  /** @var SegmentEntity */
  private $wpSegment;

  /** @var NewsletterEntity */
  private $newsletter;

  /** @var ScheduledTaskSubscribersRepository */
  private $scheduledTaskSubscribersRepository;

  public function _before() {
    parent::_before();
    $this->segmentRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);
    $this->welcomeScheduler = $this->diContainer->get(WelcomeScheduler::class);
    $this->subscriber = $this->createSubscriber('welcome_test_1@example.com');
    $this->segment = $this->segmentRepository->createOrUpdate('welcome_segment');
    $this->wpSegment = $this->segmentRepository->createOrUpdate('Wordpress');
    $this->wpSegment->setType(SegmentEntity::TYPE_WP_USERS);
    $this->segmentRepository->flush();
    $this->newsletter = $this->createWelcomeNewsletter();
    $this->scheduledTaskSubscribersRepository = $this->diContainer->get(ScheduledTaskSubscribersRepository::class);
  }

  public function testItDoesNotCreateDuplicateWelcomeNotificationSendingTasks() {
    $newsletter = $this->configureNewsletterWithOptions($this->newsletter, [
      'afterTimeNumber' => 2,
      'afterTimeType' => 'hours',
      'event' => 'segment',
      'segment' => $this->segment->getId(),
    ]);

    $existingSubscriber = $this->subscriber->getId();

    $scheduledTask = (new ScheduledTaskFactory())->create(SendingQueue::TASK_TYPE, null);
    (new SendingQueueFactory())->create($scheduledTask, $newsletter);
    $this->scheduledTaskSubscribersRepository->setSubscribers($scheduledTask, [$existingSubscriber]);

    // queue is not scheduled
    $this->welcomeScheduler->createWelcomeNotificationSendingTask($newsletter, $existingSubscriber);
    $queues = $this->entityManager->getRepository(SendingQueueEntity::class)->findAll();
    verify($queues)->arrayCount(1);

    // queue is scheduled
    $unscheduledSubscriber = $this->createSubscriber('welcome_test_2@example.com');
    $this->welcomeScheduler->createWelcomeNotificationSendingTask($newsletter, $unscheduledSubscriber->getId());
    $queues = $this->entityManager->getRepository(SendingQueueEntity::class)->findAll();
    verify($queues)->arrayCount(2);
  }

  public function testItCreatesWelcomeNotificationSendingTaskScheduledToSendInHours() {
    // queue is scheduled delivery in 2 hours
    $newsletter = $this->configureNewsletterWithOptions($this->newsletter, [
      'afterTimeNumber' => 2,
      'afterTimeType' => 'hours',
      'event' => 'segment',
      'segment' => $this->segment->getId(),
    ]);

    $currentTime = Carbon::now()->millisecond(0);
    $welcomeScheduler = $this->createWelcomeSchedulerWithMockedWPCurrentTime($currentTime);

    $welcomeScheduler->createWelcomeNotificationSendingTask($newsletter, $this->subscriber->getId());
    $this->entityManager->refresh($newsletter);
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    verify($queue->getId())->greaterThanOrEqual(1);
    verify($queue->getCountProcessed())->equals(0);
    verify($queue->getCountToProcess())->equals(1);
    verify($queue->getCountTotal())->equals(1);
    $task = $queue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    verify($task->getPriority())->equals(ScheduledTaskEntity::PRIORITY_HIGH);
    $scheduledAt = $task->getScheduledAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $scheduledAt);
    verify($scheduledAt->format('Y-m-d H:i'))
      ->equals($currentTime->addHours(2)->format('Y-m-d H:i'));
  }

  public function testItCreatesWelcomeNotificationSendingTaskScheduledToSendInDays() {
    // queue is scheduled for delivery in 2 days
    $newsletter = $this->configureNewsletterWithOptions($this->newsletter, [
      'afterTimeNumber' => 2,
      'afterTimeType' => 'days',
      'event' => 'segment',
      'segment' => $this->segment->getId(),
    ]);
    $currentTime = Carbon::now()->millisecond(0);
    $welcomeScheduler = $this->createWelcomeSchedulerWithMockedWPCurrentTime($currentTime);

    $welcomeScheduler->createWelcomeNotificationSendingTask($newsletter, $this->subscriber->getId());
    $this->entityManager->refresh($newsletter);
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    verify($queue->getId())->greaterThanOrEqual(1);
    $task = $queue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    verify($task->getPriority())->equals(ScheduledTaskEntity::PRIORITY_HIGH);
    $scheduledAt = $task->getScheduledAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $scheduledAt);
    verify($scheduledAt->format('Y-m-d H:i'))
      ->equals($currentTime->addDays(2)->format('Y-m-d H:i'));
  }

  public function testItCreatesWelcomeNotificationSendingTaskScheduledToSendInWeeks() {
    // queue is scheduled for delivery in 2 weeks
    $newsletter = $this->configureNewsletterWithOptions($this->newsletter, [
      'afterTimeNumber' => 2,
      'afterTimeType' => 'weeks',
      'event' => 'segment',
      'segment' => $this->segment->getId(),
    ]);
    $currentTime = Carbon::now()->millisecond(0);
    $welcomeScheduler = $this->createWelcomeSchedulerWithMockedWPCurrentTime($currentTime);

    $welcomeScheduler->createWelcomeNotificationSendingTask($newsletter, $this->subscriber->getId());
    $this->entityManager->refresh($newsletter);
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    verify($queue->getId())->greaterThanOrEqual(1);
    $task = $queue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    verify($task->getPriority())->equals(ScheduledTaskEntity::PRIORITY_HIGH);
    $scheduledAt = $task->getScheduledAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $scheduledAt);
    verify($scheduledAt->format('Y-m-d H:i'))
      ->equals($currentTime->addWeeks(2)->format('Y-m-d H:i'));
  }

  public function testItCreatesWelcomeNotificationSendingTaskScheduledToSendImmediately() {
    // queue is scheduled for immediate delivery
    $newsletter = $this->configureNewsletterWithOptions($this->newsletter, [
      'afterTimeNumber' => 2,
      'afterTimeType' => null,
      'event' => 'segment',
      'segment' => $this->segment->getId(),
    ]);

    $currentTime = Carbon::now()->millisecond(0);
    $welcomeScheduler = $this->createWelcomeSchedulerWithMockedWPCurrentTime($currentTime);

    $welcomeScheduler->createWelcomeNotificationSendingTask($newsletter, $this->subscriber->getId());
    $this->entityManager->refresh($newsletter);
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    verify($queue->getId())->greaterThanOrEqual(1);
    $task = $queue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    verify($task->getPriority())->equals(ScheduledTaskEntity::PRIORITY_HIGH);
    $scheduledAt = $task->getScheduledAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $scheduledAt);
    verify($scheduledAt->format('Y-m-d H:i'))
      ->equals($currentTime->format('Y-m-d H:i'));
  }

  public function testItDoesNotSchedulesSubscriberWelcomeNotificationWhenSubscriberIsNotInSegment() {
    // do not schedule when subscriber is not in segment
    $this->welcomeScheduler->scheduleSubscriberWelcomeNotification(
      $this->subscriber->getId(),
      $segments = []
    );

    // queue is not created
    $queue = $this->newsletter->getLatestQueue();
    verify($queue)->null();
  }

  public function testItSchedulesSubscriberWelcomeNotification() {
    $newsletter = $this->configureNewsletterWithOptions(
      $this->newsletter,
      [
        'event' => 'segment',
        'segment' => $this->segment->getId(),
        'afterTimeType' => 'days',
        'afterTimeNumber' => 1,
      ]
    );

    $segment2 = $this->segmentRepository->createOrUpdate('Segment 2');
    $segment3 = $this->segmentRepository->createOrUpdate('Segment 3');

    // queue is created and scheduled for delivery one day later
    $this->welcomeScheduler->scheduleSubscriberWelcomeNotification(
      $this->subscriber->getId(),
      $segments = [
        $this->segment->getId(),
        $segment2->getId(),
        $segment3->getId(),
      ]
    );
    $currentTime = Carbon::now()->millisecond(0);
    Carbon::setTestNow($currentTime); // mock carbon to return current time
    $this->entityManager->refresh($newsletter);
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $task = $queue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $scheduledAt = $task->getScheduledAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $scheduledAt);
    verify($scheduledAt->format('Y-m-d H:i'))
      ->equals($currentTime->addDay()->format('Y-m-d H:i'));

    $queues = $this->sendingQueuesRepository->findAll();
    $this->assertCount(1, $queues);
    $this->assertSame($queue, $queues[0]);
  }

  public function testItDoesNotScheduleWelcomeNotificationWhenSubscriberIsInTrash() {
    $this->configureNewsletterWithOptions(
      $this->newsletter,
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
    $this->welcomeScheduler->scheduleSubscriberWelcomeNotification(
      $trashedSubscriber->getId(),
      $segments = [$this->segment->getId()]
    );

    $queues = $this->sendingQueuesRepository->findAll();
    $this->assertEmpty($queues);
  }

  public function testItDoesNotScheduleWelcomeNotificationWhenSegmentIsInTrash() {
    $this->configureNewsletterWithOptions(
      $this->newsletter,
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
    $this->welcomeScheduler->scheduleSubscriberWelcomeNotification(
      $this->subscriber->getId(),
      $segments = [$this->segment->getId()]
    );

    $queues = $this->sendingQueuesRepository->findAll();
    $this->assertEmpty($queues);
  }

  public function itDoesNotScheduleAnythingWhenNewsletterDoesNotExist() {
    // subscriber welcome notification is not scheduled
    $this->welcomeScheduler->scheduleSubscriberWelcomeNotification(
      $this->subscriber->getId(),
      $segments = []
    );
    $queues = $this->sendingQueuesRepository->findAll();
    $this->assertEmpty($queues);

    // WP user welcome notification is not scheduled
    $this->welcomeScheduler->scheduleWPUserWelcomeNotification(
      $this->subscriber->getId(),
      $wpUser = ['roles' => ['editor']]
    );
    $queues = $this->sendingQueuesRepository->findAll();
    $this->assertEmpty($queues);
  }

  public function testItDoesNotScheduleWPUserWelcomeNotificationWhenRoleHasNotChanged() {
    $newsletter = $this->configureNewsletterWithOptions(
      $this->newsletter,
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
    $queue = $newsletter->getLatestQueue();
    verify($queue)->null();
  }

  public function testItDoesNotScheduleWPUserWelcomeNotificationWhenUserRoleDoesNotMatch() {
    $newsletter = $this->configureNewsletterWithOptions(
      $this->newsletter,
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
    $queue = $newsletter->getLatestQueue();
    verify($queue)->null();
  }

  public function testItDoesNotSchedulesWPUserWelcomeNotificationWhenSubscriberIsInTrash() {
    $newsletter = $this->configureNewsletterWithOptions(
      $this->newsletter,
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
    // queue is created and scheduled for delivery one day later
    $queue = $newsletter->getLatestQueue();
    verify($queue)->null();
  }

  public function testItDoesNotSchedulesWPUserWelcomeNotificationWhenWpSegmentIsInTrash() {
    $newsletter = $this->configureNewsletterWithOptions(
      $this->newsletter,
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
    // queue is created and scheduled for delivery one day later
    $queue = $newsletter->getLatestQueue();
    verify($queue)->null();
  }

  public function testItSchedulesWPUserWelcomeNotificationWhenUserRolesMatches() {
    $newsletter = $this->configureNewsletterWithOptions(
      $this->newsletter,
      [
        'event' => 'user',
        'role' => 'administrator',
        'afterTimeType' => 'days',
        'afterTimeNumber' => 1,
      ]
    );
    $this->welcomeScheduler->scheduleWPUserWelcomeNotification(
      $this->subscriber->getId(),
      ['roles' => ['administrator']]
    );
    $currentTime = Carbon::now()->millisecond(0);
    Carbon::setTestNow($currentTime); // mock carbon to return current time
    // queue is created and scheduled for delivery one day later
    $this->entityManager->refresh($newsletter);
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $task = $queue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $scheduledAt = $task->getScheduledAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $scheduledAt);
    verify($scheduledAt->format('Y-m-d H:i'))
      ->equals($currentTime->addDay()->format('Y-m-d H:i'));
  }

  public function testItSchedulesWPUserWelcomeNotificationWhenUserHasAnyRole() {
    $newsletter = $this->configureNewsletterWithOptions(
      $this->newsletter,
      [
        'event' => 'user',
        'role' => WelcomeScheduler::WORDPRESS_ALL_ROLES,
        'afterTimeType' => 'days',
        'afterTimeNumber' => 1,
      ]
    );
    $this->welcomeScheduler->scheduleWPUserWelcomeNotification(
      $this->subscriber->getId(),
      ['roles' => ['administrator']]
    );
    $currentTime = Carbon::now()->millisecond(0);
    Carbon::setTestNow($currentTime); // mock carbon to return current time
    // queue is created and scheduled for delivery one day later
    $this->entityManager->refresh($newsletter);
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $task = $queue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $scheduledAt = $task->getScheduledAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $scheduledAt);
    verify($scheduledAt->format('Y-m-d H:i'))
      ->equals($currentTime->addDay()->format('Y-m-d H:i'));
  }

  private function createWelcomeNewsletter($status = NewsletterEntity::STATUS_ACTIVE): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('Welcome Newsletter');
    $newsletter->setType(NewsletterEntity::TYPE_WELCOME);
    $newsletter->setStatus($status);
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();
    return $newsletter;
  }

  private function configureNewsletterWithOptions(NewsletterEntity $newsletter, array $options): NewsletterEntity {
    $newsletterOptionsFactory = new NewsletterOption();
    $newsletterOptionsFactory->createMultipleOptions($newsletter, $options);
    return $newsletter;
  }

  private function createSubscriber($email): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriber->setEmail($email);
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    return $subscriber;
  }

  private function createWelcomeSchedulerWithMockedWPCurrentTime($currentTime): WelcomeScheduler {
    $wpMock = $this->createMock(WPFunctions::class);
    $wpMock->expects($this->any())
      ->method('currentTime')
      ->willReturn($currentTime->getTimestamp());
    return new WelcomeScheduler(
      $this->diContainer->get(EntityManager::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->segmentRepository,
      $this->diContainer->get(NewslettersRepository::class),
      $this->diContainer->get(ScheduledTasksRepository::class),
      new Scheduler($wpMock, $this->diContainer->get(NewslettersRepository::class))
    );
  }
}
