<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Config\Hooks;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Newsletter\NewsletterPostsRepository;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Options\NewsletterOptionsRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\NewsletterOption as NewsletterOptionsFactory;
use MailPoet\Test\DataFactories\ScheduledTask;
use MailPoet\Test\DataFactories\SendingQueue as SendingQueueFactory;
use MailPoet\WP\DateTime;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Posts as WPPosts;
use MailPoetVendor\Carbon\Carbon;

class PostNotificationTest extends \MailPoetTest {

  /** @var PostNotificationScheduler */
  private $postNotificationScheduler;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var NewsletterPostsRepository */
  private $newsletterPostsRepository;

  /** @var NewsletterOptionsRepository */
  private $newsletterOptionsRepository;

  /** @var Hooks */
  private $hooks;

  /** @var Scheduler */
  private $scheduler;

  /** @var NewsletterOptionsFactory */
  private $newsletterOptionsFactory;

  /*** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  public function _before() {
    parent::_before();
    $this->postNotificationScheduler = $this->diContainer->get(PostNotificationScheduler::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->newsletterPostsRepository = $this->diContainer->get(NewsletterPostsRepository::class);
    $this->newsletterOptionsRepository = $this->diContainer->get(NewsletterOptionsRepository::class);
    $this->sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);
    $this->newsletterOptionsFactory = new NewsletterOptionsFactory();
    $this->hooks = $this->diContainer->get(Hooks::class);
    $this->scheduler = $this->diContainer->get(Scheduler::class);
  }

  public function testItCreatesPostNotificationSendingTask() {
    $newsletter = $this->createNewsletter();
    $this->newsletterOptionsFactory->createMultipleOptions($newsletter, [
      NewsletterOptionFieldEntity::NAME_SCHEDULE => '* 5 * * *',
    ]);

    // new queue record should be created
    $sendingTask = $this->postNotificationScheduler->createPostNotificationSendingTask($newsletter);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $sendingTask);
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingTask->getSendingQueue());
    expect($this->sendingQueuesRepository->findBy(['newsletter' => $newsletter]))->count(1);
    $this->assertInstanceOf(NewsletterEntity::class, $sendingTask->getSendingQueue()->getNewsletter());
    expect($sendingTask->getSendingQueue()->getNewsletter()->getId())->equals($newsletter->getId());

    expect($sendingTask->getStatus())->equals(SendingQueueEntity::STATUS_SCHEDULED);


    expect($sendingTask->getScheduledAt())->equals($this->scheduler->getNextRunDate('* 5 * * *'));
    expect($sendingTask->getPriority())->equals(SendingQueueEntity::PRIORITY_MEDIUM);

    // duplicate queue record should not be created
    $newsletterId = $newsletter->getId();
    $this->entityManager->clear();
    $newsletter = $this->newslettersRepository->findOneById($newsletterId);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $this->postNotificationScheduler->createPostNotificationSendingTask($newsletter);
    expect($this->sendingQueuesRepository->findBy(['newsletter' => $newsletter]))->count(1);
  }

  public function testItCreatesPostNotificationSendingTaskIfAPausedNotificationExists() {
    $newsletter = $this->createNewsletter();
    $this->newsletterOptionsFactory->createMultipleOptions($newsletter, [
      NewsletterOptionFieldEntity::NAME_SCHEDULE => '* 5 * * *',
    ]);

    // new queue record should be created
    $sendingTask = $this->postNotificationScheduler->createPostNotificationSendingTask($newsletter);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $sendingTask);
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingTask->getSendingQueue());
    $this->sendingQueuesRepository->pause($sendingTask->getSendingQueue());

    // another queue record should be created because the first one was paused
    $scheduleOption = $newsletter->getOption(NewsletterOptionFieldEntity::NAME_SCHEDULE);
    $this->assertInstanceOf(NewsletterOptionEntity::class, $scheduleOption);
    $scheduleOption->setValue('* 10 * * *'); // different time to not clash with the first queue
    $this->newsletterOptionsRepository->flush();
    $sendingTask = $this->postNotificationScheduler->createPostNotificationSendingTask($newsletter);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $sendingTask);
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingTask->getSendingQueue());
    expect($this->sendingQueuesRepository->findBy(['newsletter' => $newsletter]))->count(2);
    $this->assertInstanceOf(NewsletterEntity::class, $sendingTask->getSendingQueue()->getNewsletter());
    expect($sendingTask->getSendingQueue()->getNewsletter()->getId())->equals($newsletter->getId());

    expect($sendingTask->getStatus())->equals(SendingQueueEntity::STATUS_SCHEDULED);
    expect($sendingTask->getScheduledAt())->equals($this->scheduler->getNextRunDate('* 10 * * *'));
    expect($sendingTask->getPriority())->equals(SendingQueueEntity::PRIORITY_MEDIUM);

    // duplicate queue record should not be created
    $newsletterId = $newsletter->getId();
    $this->entityManager->clear();
    $newsletter = $this->newslettersRepository->findOneById($newsletterId);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $this->postNotificationScheduler->createPostNotificationSendingTask($newsletter);
    expect($this->sendingQueuesRepository->findBy(['newsletter' => $newsletter]))->count(2);
  }

  public function testItDoesNotSchedulePostNotificationWhenNotificationWasAlreadySentForPost() {
    $postId = 10;
    $newsletter = $this->createNewsletter();
    $newsletterPost = $this->createPost($newsletter, $postId);

    // queue is not created when notification was already sent for the post
    $this->postNotificationScheduler->schedulePostNotification($postId);
    $queue = $this->sendingQueuesRepository->findOneBy(['newsletter' => $newsletter]);
    expect($queue)->null();
  }

  public function testItSchedulesPostNotification() {
    $newsletter = $this->createNewsletter();
    $this->newsletterOptionsFactory->createMultipleOptions($newsletter, [
      NewsletterOptionFieldEntity::NAME_SCHEDULE => '0 5 * * *',
    ]);

    // queue is created and scheduled for delivery one day later at 5 a.m.
    $this->postNotificationScheduler->schedulePostNotification($postId = 10);
    $currentTime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    Carbon::setTestNow($currentTime); // mock carbon to return current time
    $nextRunDate = ($currentTime->hour < 5) ?
      $currentTime :
      $currentTime->addDay();
    $queue = $this->sendingQueuesRepository->findOneBy(['newsletter' => $newsletter]);
    expect($queue->getTask()->getScheduledAt()->format(DateTime::DEFAULT_DATE_TIME_FORMAT))
      ->equals($nextRunDate->format('Y-m-d 05:00:00'));
  }

  public function testItProcessesPostNotificationScheduledForDailyDelivery() {
    $newsletter = $this->createNewsletter();
    $this->newsletterOptionsFactory->createMultipleOptions($newsletter, [
      NewsletterOptionFieldEntity::NAME_INTERVAL_TYPE => PostNotificationScheduler::INTERVAL_DAILY,
      NewsletterOptionFieldEntity::NAME_MONTH_DAY => null,
      NewsletterOptionFieldEntity::NAME_NTH_WEEK_DAY => null,
      NewsletterOptionFieldEntity::NAME_WEEK_DAY => null,
      NewsletterOptionFieldEntity::NAME_TIME_OF_DAY => 50400, // 2 p.m.
      NewsletterOptionFieldEntity::NAME_SCHEDULE => '* * * * *',
    ]);

    $this->postNotificationScheduler->processPostNotificationSchedule($newsletter);

    $scheduleOption = $newsletter->getOption(NewsletterOptionFieldEntity::NAME_SCHEDULE);
    $this->assertInstanceOf(NewsletterOptionEntity::class, $scheduleOption);
    $currentTime = 1483275600; // Sunday, 1 January 2017 @ 1:00pm (UTC)
    expect($this->scheduler->getNextRunDate($scheduleOption->getValue(), $currentTime))
      ->equals('2017-01-01 14:00:00');
  }

  public function testItProcessesPostNotificationScheduledForWeeklyDelivery() {
    $newsletter = $this->createNewsletter();

    // weekly notification is scheduled every Tuesday at 14:00
    $this->newsletterOptionsFactory->createMultipleOptions($newsletter, [
      NewsletterOptionFieldEntity::NAME_INTERVAL_TYPE => PostNotificationScheduler::INTERVAL_WEEKLY,
      NewsletterOptionFieldEntity::NAME_MONTH_DAY => null,
      NewsletterOptionFieldEntity::NAME_NTH_WEEK_DAY => null,
      NewsletterOptionFieldEntity::NAME_WEEK_DAY => Carbon::TUESDAY,
      NewsletterOptionFieldEntity::NAME_TIME_OF_DAY => 50400, // 2 p.m.
      NewsletterOptionFieldEntity::NAME_SCHEDULE => '* * * * *',
    ]);

    $this->postNotificationScheduler->processPostNotificationSchedule($newsletter);

    $scheduleOption = $newsletter->getOption(NewsletterOptionFieldEntity::NAME_SCHEDULE);
    $this->assertInstanceOf(NewsletterOptionEntity::class, $scheduleOption);
    $currentTime = 1483275600; // Sunday, 1 January 2017 @ 1:00pm (UTC)
    expect($this->scheduler->getNextRunDate($scheduleOption->getValue(), $currentTime))
      ->equals('2017-01-03 14:00:00');
  }

  public function testItProcessesPostNotificationScheduledForMonthlyDeliveryOnSpecificDay() {
    $newsletter = $this->createNewsletter();

    // monthly notification is scheduled every 20th day at 14:00
    $this->newsletterOptionsFactory->createMultipleOptions($newsletter, [
      NewsletterOptionFieldEntity::NAME_INTERVAL_TYPE => PostNotificationScheduler::INTERVAL_MONTHLY,
      NewsletterOptionFieldEntity::NAME_MONTH_DAY => 19,
      NewsletterOptionFieldEntity::NAME_NTH_WEEK_DAY => null,
      NewsletterOptionFieldEntity::NAME_WEEK_DAY => null,
      NewsletterOptionFieldEntity::NAME_TIME_OF_DAY => 50400, // 2 p.m.
      NewsletterOptionFieldEntity::NAME_SCHEDULE => '* * * * *',
    ]);

    $this->postNotificationScheduler->processPostNotificationSchedule($newsletter);
    $scheduleOption = $newsletter->getOption(NewsletterOptionFieldEntity::NAME_SCHEDULE);
    $this->assertInstanceOf(NewsletterOptionEntity::class, $scheduleOption);
    $currentTime = 1483275600; // Sunday, 1 January 2017 @ 1:00pm (UTC)
    expect($this->scheduler->getNextRunDate($scheduleOption->getValue(), $currentTime))
      ->equals('2017-01-19 14:00:00');
  }

  public function testItProcessesPostNotificationScheduledForMonthlyDeliveryOnLastWeekDay() {
    $newsletter = $this->createNewsletter();

    // monthly notification is scheduled every last Saturday at 14:00
    $this->newsletterOptionsFactory->createMultipleOptions($newsletter, [
      NewsletterOptionFieldEntity::NAME_INTERVAL_TYPE => PostNotificationScheduler::INTERVAL_NTHWEEKDAY,
      NewsletterOptionFieldEntity::NAME_MONTH_DAY => null,
      NewsletterOptionFieldEntity::NAME_NTH_WEEK_DAY => 'L', // L = last
      NewsletterOptionFieldEntity::NAME_WEEK_DAY => Carbon::SATURDAY,
      NewsletterOptionFieldEntity::NAME_TIME_OF_DAY => 50400, // 2 p.m.
      NewsletterOptionFieldEntity::NAME_SCHEDULE => '* * * * *',
    ]);

    $this->postNotificationScheduler->processPostNotificationSchedule($newsletter);
    $scheduleOption = $newsletter->getOption(NewsletterOptionFieldEntity::NAME_SCHEDULE);
    $this->assertInstanceOf(NewsletterOptionEntity::class, $scheduleOption);
    $currentTime = 1485694800; // Sunday, 29 January 2017 @ 1:00pm (UTC)
    expect($this->scheduler->getNextRunDate($scheduleOption->getValue(), $currentTime))
      ->equals('2017-02-25 14:00:00');
  }

  public function testItProcessesPostNotificationScheduledForImmediateDelivery() {
    $newsletter = $this->createNewsletter();

    // notification is scheduled immediately (next minute)
    $this->newsletterOptionsFactory->createMultipleOptions($newsletter, [
      NewsletterOptionFieldEntity::NAME_INTERVAL_TYPE => PostNotificationScheduler::INTERVAL_IMMEDIATELY,
      NewsletterOptionFieldEntity::NAME_MONTH_DAY => null,
      NewsletterOptionFieldEntity::NAME_NTH_WEEK_DAY => null,
      NewsletterOptionFieldEntity::NAME_WEEK_DAY => null,
      NewsletterOptionFieldEntity::NAME_TIME_OF_DAY => null,
      NewsletterOptionFieldEntity::NAME_SCHEDULE => '* * * * *',
    ]);

    $this->postNotificationScheduler->processPostNotificationSchedule($newsletter);
    $scheduleOption = $newsletter->getOption(NewsletterOptionFieldEntity::NAME_SCHEDULE);
    $this->assertInstanceOf(NewsletterOptionEntity::class, $scheduleOption);
    $currentTime = 1483275600; // Sunday, 1 January 2017 @ 1:00pm (UTC)
    expect($this->scheduler->getNextRunDate($scheduleOption->getValue(), $currentTime))
      ->equals('2017-01-01 13:01:00');
  }

  public function testUnsearchablePostTypeDoesNotSchedulePostNotification() {
    $newsletter = $this->createNewsletter();

    $this->newsletterOptionsFactory->createMultipleOptions($newsletter, [
      NewsletterOptionFieldEntity::NAME_INTERVAL_TYPE => PostNotificationScheduler::INTERVAL_IMMEDIATELY,
      NewsletterOptionFieldEntity::NAME_SCHEDULE => '* * * * *',
    ]);

    $this->_removePostNotificationHooks();
    register_post_type('post', ['exclude_from_search' => true]);
    $this->hooks->setupPostNotifications();

    $postData = [
      'post_title' => 'title',
      'post_status' => 'publish',
    ];
    wp_insert_post($postData);

    $queue = $this->sendingQueuesRepository->findOneBy(['newsletter' => $newsletter]);
    expect($queue)->null();

    $this->_removePostNotificationHooks();
    register_post_type('post', ['exclude_from_search' => false]);
    $this->hooks->setupPostNotifications();

    wp_insert_post($postData);

    $queue = $this->sendingQueuesRepository->findOneBy(['newsletter' => $newsletter]);
    expect($queue)->notNull();
  }

  public function testSchedulerWontRunIfUnsentNotificationHistoryExists() {
    $newsletter = $this->createNewsletter();

    $this->newsletterOptionsFactory->createMultipleOptions($newsletter, [
      NewsletterOptionFieldEntity::NAME_INTERVAL_TYPE => PostNotificationScheduler::INTERVAL_IMMEDIATELY,
      NewsletterOptionFieldEntity::NAME_SCHEDULE => '* * * * *',
    ]);

    $notificationHistory = (new NewsletterFactory())
      ->withSubject($newsletter->getSubject())
      ->withType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY)
      ->withStatus(NewsletterEntity::STATUS_SENDING)
      ->withParent($newsletter)
      ->create();

    $task = (new ScheduledTask())->create(
      SendingQueue::TASK_TYPE,
      SendingQueueEntity::STATUS_SCHEDULED, Carbon::now()
      ->addDay()
    );

    (new SendingQueueFactory())->create($task, $notificationHistory);

    $postData = [
      'post_title' => 'title',
      'post_status' => 'publish',
    ];

    // because the hooks work after deserialization entityManager incorrectly, we need to remove the hooks and setup them again
    $this->_removePostNotificationHooks();
    $this->hooks->setupPostNotifications();
    wp_insert_post($postData);

    $queue = $this->sendingQueuesRepository->findOneBy(['newsletter' => $newsletter]);
    expect($queue)->null();
  }

  public function _removePostNotificationHooks() {
    foreach (WPPosts::getTypes() as $postType) {
      remove_filter(
        'publish_' . $postType,
        [$this->postNotificationScheduler, 'transitionHook'],
        10
      );
    }
  }

  private function createNewsletter(): NewsletterEntity {
    return (new NewsletterFactory())
      ->withType(NewsletterEntity::TYPE_NOTIFICATION)
      ->withStatus(NewsletterEntity::STATUS_ACTIVE)
      ->withSubject('Testing subject')
      ->create();
  }

  private function createPost(NewsletterEntity $newsletter, int $postId): NewsletterPostEntity {
    $newsletterPost = new NewsletterPostEntity($newsletter, $postId);
    $this->newsletterPostsRepository->persist($newsletterPost);
    $this->newsletterPostsRepository->flush();
    return $newsletterPost;
  }

  public function _after() {
    parent::_after();
    Carbon::setTestNow();
  }
}
