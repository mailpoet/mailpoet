<?php

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Config\Hooks;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Newsletter\NewsletterPostsRepository;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Options\NewsletterOptionsRepository;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Test\DataFactories\NewsletterOption as NewsletterOptionsFactory;
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

  public function _before() {
    parent::_before();
    $this->postNotificationScheduler = $this->diContainer->get(PostNotificationScheduler::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->newsletterPostsRepository = $this->diContainer->get(NewsletterPostsRepository::class);
    $this->newsletterOptionsRepository = $this->diContainer->get(NewsletterOptionsRepository::class);
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
    $queue = $this->postNotificationScheduler->createPostNotificationSendingTask($newsletter);
    assert($queue instanceof SendingTask);
    expect(SendingQueue::where('newsletter_id', $newsletter->getId())->findMany())->count(1);
    expect($queue->newsletterId)->equals($newsletter->getId());
    expect($queue->status)->equals(SendingQueue::STATUS_SCHEDULED);

    expect($queue->scheduledAt)->equals($this->scheduler->getNextRunDate('* 5 * * *'));
    expect($queue->priority)->equals(SendingQueue::PRIORITY_MEDIUM);

    // duplicate queue record should not be created
    $newsletterId = $newsletter->getId();
    $this->entityManager->clear();
    $newsletter = $this->newslettersRepository->findOneById($newsletterId);
    assert($newsletter instanceof NewsletterEntity);
    $this->postNotificationScheduler->createPostNotificationSendingTask($newsletter);
    expect(SendingQueue::where('newsletter_id', $newsletter->getId())->findMany())->count(1);
  }

  public function testItCreatesPostNotificationSendingTaskIfAPausedNotificationExists() {
    $newsletter = $this->createNewsletter();
    $this->newsletterOptionsFactory->createMultipleOptions($newsletter, [
      NewsletterOptionFieldEntity::NAME_SCHEDULE => '* 5 * * *',
    ]);

    // new queue record should be created
    $queueToBePaused = $this->postNotificationScheduler->createPostNotificationSendingTask($newsletter);
    assert($queueToBePaused instanceof SendingTask);
    $queueToBePaused->task()->pause();

    // another queue record should be created because the first one was paused
    $scheduleOption = $newsletter->getOption(NewsletterOptionFieldEntity::NAME_SCHEDULE);
    assert($scheduleOption instanceof NewsletterOptionEntity);
    $scheduleOption->setValue('* 10 * * *'); // different time to not clash with the first queue
    $this->newsletterOptionsRepository->flush();
    $queue = $this->postNotificationScheduler->createPostNotificationSendingTask($newsletter);
    assert($queue instanceof SendingTask);
    expect(SendingQueue::where('newsletter_id', $newsletter->getId())->findMany())->count(2);
    expect($queue->newsletterId)->equals($newsletter->getId());
    expect($queue->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect($queue->scheduledAt)->equals($this->scheduler->getNextRunDate('* 10 * * *'));
    expect($queue->priority)->equals(SendingQueue::PRIORITY_MEDIUM);

    // duplicate queue record should not be created
    $newsletterId = $newsletter->getId();
    $this->entityManager->clear();
    $newsletter = $this->newslettersRepository->findOneById($newsletterId);
    assert($newsletter instanceof NewsletterEntity);
    $this->postNotificationScheduler->createPostNotificationSendingTask($newsletter);
    expect(SendingQueue::where('newsletter_id', $newsletter->getId())->findMany())->count(2);
  }

  public function testItDoesNotSchedulePostNotificationWhenNotificationWasAlreadySentForPost() {
    $postId = 10;
    $newsletter = $this->createNewsletter();
    $newsletterPost = $this->createPost($newsletter, $postId);

    // queue is not created when notification was already sent for the post
    $this->postNotificationScheduler->schedulePostNotification($postId);
    $queue = SendingQueue::findTaskByNewsletterId($newsletter->getId())
      ->findOne();
    expect($queue)->false();
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
    $queue = SendingQueue::findTaskByNewsletterId($newsletter->getId())
      ->findOne();
    expect($queue->scheduledAt)->startsWith($nextRunDate->format('Y-m-d 05:00'));
  }

  public function testItProcessesPostNotificationScheduledForDailyDelivery() {
    $newsletter = $this->createNewsletter();
    $this->newsletterOptionsFactory->createMultipleOptions($newsletter, [
      NewsletterOptionFieldEntity::NAME_INTERVAL_TYPE => PostNotificationScheduler::INTERVAL_DAILY,
      NewsletterOptionFieldEntity::NAME_MONTH_DAY => null,
      NewsletterOptionFieldEntity::NAME_NTH_WEEK_DAY => null,
      NewsletterOptionFieldEntity::NAME_WEK_DAY => null,
      NewsletterOptionFieldEntity::NAME_TIME_OF_DAY => 50400, // 2 p.m.
      NewsletterOptionFieldEntity::NAME_SCHEDULE => '* * * * *',
    ]);

    $this->postNotificationScheduler->processPostNotificationSchedule($newsletter);

    $scheduleOption = $newsletter->getOption(NewsletterOptionFieldEntity::NAME_SCHEDULE);
    assert($scheduleOption instanceof NewsletterOptionEntity);
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
      NewsletterOptionFieldEntity::NAME_WEK_DAY => Carbon::TUESDAY,
      NewsletterOptionFieldEntity::NAME_TIME_OF_DAY => 50400, // 2 p.m.
      NewsletterOptionFieldEntity::NAME_SCHEDULE => '* * * * *',
    ]);

    $this->postNotificationScheduler->processPostNotificationSchedule($newsletter);

    $scheduleOption = $newsletter->getOption(NewsletterOptionFieldEntity::NAME_SCHEDULE);
    assert($scheduleOption instanceof NewsletterOptionEntity);
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
      NewsletterOptionFieldEntity::NAME_WEK_DAY => null,
      NewsletterOptionFieldEntity::NAME_TIME_OF_DAY => 50400, // 2 p.m.
      NewsletterOptionFieldEntity::NAME_SCHEDULE => '* * * * *',
    ]);

    $this->postNotificationScheduler->processPostNotificationSchedule($newsletter);
    $scheduleOption = $newsletter->getOption(NewsletterOptionFieldEntity::NAME_SCHEDULE);
    assert($scheduleOption instanceof NewsletterOptionEntity);
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
      NewsletterOptionFieldEntity::NAME_WEK_DAY => Carbon::SATURDAY,
      NewsletterOptionFieldEntity::NAME_TIME_OF_DAY => 50400, // 2 p.m.
      NewsletterOptionFieldEntity::NAME_SCHEDULE => '* * * * *',
    ]);

    $this->postNotificationScheduler->processPostNotificationSchedule($newsletter);
    $scheduleOption = $newsletter->getOption(NewsletterOptionFieldEntity::NAME_SCHEDULE);
    assert($scheduleOption instanceof NewsletterOptionEntity);
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
      NewsletterOptionFieldEntity::NAME_WEK_DAY => null,
      NewsletterOptionFieldEntity::NAME_TIME_OF_DAY => null,
      NewsletterOptionFieldEntity::NAME_SCHEDULE => '* * * * *',
    ]);

    $this->postNotificationScheduler->processPostNotificationSchedule($newsletter);
    $scheduleOption = $newsletter->getOption(NewsletterOptionFieldEntity::NAME_SCHEDULE);
    assert($scheduleOption instanceof NewsletterOptionEntity);
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

    $queue = SendingQueue::findTaskByNewsletterId($newsletter->getId())->findOne();
    expect($queue)->equals(false);

    $this->_removePostNotificationHooks();
    register_post_type('post', ['exclude_from_search' => false]);
    $this->hooks->setupPostNotifications();

    wp_insert_post($postData);

    $queue = SendingQueue::findTaskByNewsletterId($newsletter->getId())->findOne();
    expect($queue)->notequals(false);
  }

  public function testSchedulerWontRunIfUnsentNotificationHistoryExists() {
    $newsletter = $this->createNewsletter();

    $this->newsletterOptionsFactory->createMultipleOptions($newsletter, [
      NewsletterOptionFieldEntity::NAME_INTERVAL_TYPE => PostNotificationScheduler::INTERVAL_IMMEDIATELY,
      NewsletterOptionFieldEntity::NAME_SCHEDULE => '* * * * *',
    ]);

    $notificationHistory = new NewsletterEntity();
    $notificationHistory->setType(Newsletter::TYPE_NOTIFICATION_HISTORY);
    $notificationHistory->setStatus(Newsletter::STATUS_SENDING);
    $notificationHistory->setParent($newsletter);
    $notificationHistory->setSubject($newsletter->getSubject());
    $this->newslettersRepository->persist($notificationHistory);
    $this->newslettersRepository->flush();

    $sendingTask = SendingTask::create();
    $sendingTask->newsletterId = $notificationHistory->getId();
    $sendingTask->status = SendingQueue::STATUS_SCHEDULED;
    $sendingTask->save();

    $postData = [
      'post_title' => 'title',
      'post_status' => 'publish',
    ];

    // because the hooks work after deserialization entityManager incorrectly, we need to remove the hooks and setup them again
    $this->_removePostNotificationHooks();
    $this->hooks->setupPostNotifications();
    wp_insert_post($postData);

    $queue = SendingQueue::findTaskByNewsletterId($newsletter->getId())->findOne();
    expect($queue)->equals(false);
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
    $newsletter = new NewsletterEntity();
    $newsletter->setType(Newsletter::TYPE_NOTIFICATION);
    $newsletter->setStatus(Newsletter::STATUS_ACTIVE);
    $newsletter->setSubject('Testing subject');
    $this->newslettersRepository->persist($newsletter);
    $this->newslettersRepository->flush();
    return $newsletter;
  }

  private function createPost(NewsletterEntity $newsletter, int $postId): NewsletterPostEntity {
    $newsletterPost = new NewsletterPostEntity($newsletter, $postId);
    $this->newsletterPostsRepository->persist($newsletterPost);
    $this->newsletterPostsRepository->flush();
    return $newsletterPost;
  }

  public function _after() {
    Carbon::setTestNow();
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(NewsletterOptionEntity::class);
    $this->truncateEntity(NewsletterOptionFieldEntity::class);
    $this->truncateEntity(NewsletterPostEntity::class);
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(ScheduledTaskSubscriberEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
  }
}
