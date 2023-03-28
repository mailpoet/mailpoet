<?php declare(strict_types = 1);

namespace MailPoet\Newsletter;

use Codeception\Util\Fixtures;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\Entities\NewsletterSegmentEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\StatisticsWooCommercePurchaseEntity;
use MailPoet\Entities\StatsNotificationEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoetVendor\Carbon\Carbon;

class NewsletterRepositoryTest extends \MailPoetTest {
  /** @var NewslettersRepository */
  private $repository;

  /** @var ScheduledTaskSubscribersRepository */
  private $taskSubscribersRepository;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(NewslettersRepository::class);
    $this->taskSubscribersRepository = $this->diContainer->get(ScheduledTaskSubscribersRepository::class);
  }

  public function testItBulkTrashNewslettersAndChildren() {
    $standardNewsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD);
    $this->createQueueWithTaskAndSegmentAndSubscribers($standardNewsletter);
    $notification = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION, NewsletterEntity::STATUS_ACTIVE);
    $notificationHistory = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, NewsletterEntity::STATUS_SCHEDULED, $notification);
    $this->createQueueWithTaskAndSegmentAndSubscribers($notificationHistory);
    $this->repository->bulkTrash([$standardNewsletter->getId(), $notification->getId()]);
    $this->entityManager->refresh($standardNewsletter);
    $this->entityManager->refresh($notification);

    // Should trash the newsletters
    expect($standardNewsletter->getDeletedAt())->notNull();
    expect($notification->getDeletedAt())->notNull();

    // Should trash sending queue and task
    $standardQueue = $standardNewsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $standardQueue);
    $this->entityManager->refresh($standardQueue);
    expect($standardQueue->getDeletedAt())->notNull();
    $scheduledTask = $standardQueue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $this->entityManager->refresh($scheduledTask);
    expect($scheduledTask->getDeletedAt())->notNull();

    // Should trash children + task + queue
    $this->entityManager->refresh($notificationHistory);
    expect($notificationHistory->getDeletedAt())->notNull();
    $notificationHistory = $notificationHistory->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $notificationHistory);
    $this->entityManager->refresh($notificationHistory);
    expect($notificationHistory->getDeletedAt())->notNull();
    $scheduledTask = $notificationHistory->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $this->entityManager->refresh($scheduledTask);
    expect($scheduledTask->getDeletedAt())->notNull();
  }

  public function testItBulkRestoresNewslettersAndChildren() {
    $standardNewsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD, NewsletterEntity::STATUS_SENDING);
    $this->createQueueWithTaskAndSegmentAndSubscribers($standardNewsletter, null); // Null for scheduled task being processed
    $notification = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION, NewsletterEntity::STATUS_ACTIVE);
    $notificationHistory = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, NewsletterEntity::STATUS_SCHEDULED, $notification);
    $this->createQueueWithTaskAndSegmentAndSubscribers($notificationHistory);
    // Trash
    $this->repository->bulkTrash([$standardNewsletter->getId(), $notification->getId()]);
    // Restore
    $this->repository->bulkRestore([$standardNewsletter->getId(), $notification->getId()]);
    $this->entityManager->refresh($standardNewsletter);
    $this->entityManager->refresh($notification);

    // Should trash the newsletters
    expect($standardNewsletter->getDeletedAt())->null();
    expect($notification->getDeletedAt())->null();
    expect($standardNewsletter->getStatus())->equals(NewsletterEntity::STATUS_SENDING);
    expect($notification->getStatus())->equals(NewsletterEntity::STATUS_ACTIVE);

    // Should restore sending queue and task
    $standardQueue = $standardNewsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $standardQueue);
    $this->entityManager->refresh($standardQueue);
    expect($standardQueue->getDeletedAt())->null();
    $scheduledTask = $standardQueue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $this->entityManager->refresh($scheduledTask);
    expect($scheduledTask->getDeletedAt())->null();
    // Pause sending tasks which were in progress
    expect($scheduledTask->getStatus())->equals(ScheduledTaskEntity::STATUS_PAUSED);

    // Should restore children + task + queue
    $this->entityManager->refresh($notificationHistory);
    expect($notificationHistory->getDeletedAt())->null();
    $notificationHistoryQueue = $notificationHistory->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $notificationHistoryQueue);
    $this->entityManager->refresh($notificationHistoryQueue);
    expect($notificationHistoryQueue->getDeletedAt())->null();
    $scheduledTask = $notificationHistoryQueue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $this->entityManager->refresh($scheduledTask);
    expect($scheduledTask->getDeletedAt())->null();
    expect($scheduledTask->getStatus())->equals(ScheduledTaskEntity::STATUS_SCHEDULED);
  }

  public function testItBulkDeleteNewslettersAndChildren() {
    $standardNewsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD, NewsletterEntity::STATUS_SENDING);
    $standardQueue = $this->createQueueWithTaskAndSegmentAndSubscribers($standardNewsletter, null); // Null for scheduled task being processed
    $notification = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION, NewsletterEntity::STATUS_ACTIVE);
    $notificationHistory = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, NewsletterEntity::STATUS_SCHEDULED, $notification);
    $notificationHistoryQueue = $this->createQueueWithTaskAndSegmentAndSubscribers($notificationHistory);

    $standardSegment = $standardNewsletter->getNewsletterSegments()->first();
    $this->assertInstanceOf(NewsletterSegmentEntity::class, $standardSegment);
    $standardScheduledTaks = $standardQueue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $standardScheduledTaks);
    $standardScheduledTaskSubscriber = $this->taskSubscribersRepository->findOneBy(['task' => $standardScheduledTaks]);
    $this->assertInstanceOf(ScheduledTaskSubscriberEntity::class, $standardScheduledTaskSubscriber);
    $notificationHistoryScheduledTask = $notificationHistoryQueue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $notificationHistoryScheduledTask);
    $notificationHistorySegment = $notificationHistory->getNewsletterSegments()->first();
    $this->assertInstanceOf(NewsletterSegmentEntity::class, $notificationHistorySegment);
    $notificationHistoryScheduledTaskSubscriber = $this->taskSubscribersRepository->findOneBy(['task' => $notificationHistoryScheduledTask]);
    $this->assertInstanceOf(ScheduledTaskSubscriberEntity::class, $notificationHistoryScheduledTaskSubscriber);
    $standardStatsNotification = $this->createStatNotification($standardNewsletter);
    $standardStatsNotificationScheduledTask = $standardStatsNotification->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $standardStatsNotificationScheduledTask);
    $notificationHistoryStatsNotification = $this->createStatNotification($notificationHistory);
    $notificationHistoryStatsNotificationScheduledTask = $notificationHistoryStatsNotification->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $notificationHistoryStatsNotificationScheduledTask);
    $standardLink = $this->createNewsletterLink($standardNewsletter, $standardQueue);
    $notificationHistoryLink = $this->createNewsletterLink($notificationHistory, $notificationHistoryQueue);
    $optionField = $this->createNewsletterOptionField(NewsletterEntity::TYPE_NOTIFICATION, 'option');
    $optionValue = $this->createNewsletterOption($notificationHistory, $optionField, 'value');
    $newsletterPost = $this->createNewsletterPost($notification, 1);

    $subscriber = $standardScheduledTaskSubscriber->getSubscriber();
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $statisticsNewsletter = $this->createNewsletterStatistics($standardNewsletter, $standardQueue, $subscriber);
    $statisticsOpen = $this->createOpenStatistics($standardNewsletter, $standardQueue, $subscriber);
    $statisticsClick = $this->createClickStatistics($standardNewsletter, $standardQueue, $subscriber, $standardLink);
    $statisticsPurchase = $this->createPurchaseStatistics($standardNewsletter, $standardQueue, $statisticsClick, $subscriber);

    // Trash
    $this->repository->bulkTrash([$standardNewsletter->getId(), $notification->getId()]);
    // Delete
    $this->repository->bulkDelete([$standardNewsletter->getId(), $notification->getId()]);

    // Clear entity manager to forget all entities
    $this->entityManager->clear();

    // Check they were all deleted
    // Newsletters
    expect($this->repository->findOneById($standardNewsletter->getId()))->null();
    expect($this->repository->findOneById($notification->getId()))->null();
    expect($this->repository->findOneById($notificationHistory->getId()))->null();

    // Sending queues
    expect($this->entityManager->find(SendingQueueEntity::class, $standardQueue->getId()))->null();
    expect($this->entityManager->find(SendingQueueEntity::class, $notificationHistoryQueue->getId()))->null();

    // Scheduled tasks subscribers
    expect($this->taskSubscribersRepository->findOneBy(['task' => $standardScheduledTaks]))->null();
    expect($this->taskSubscribersRepository->findOneBy(['task' => $notificationHistoryScheduledTask]))->null();

    // Scheduled tasks
    expect($this->entityManager->find(ScheduledTaskEntity::class, $standardScheduledTaks->getId()))->null();
    expect($this->entityManager->find(ScheduledTaskEntity::class, $notificationHistoryScheduledTask->getId()))->null();

    // Newsletter segments
    expect($this->entityManager->find(NewsletterSegmentEntity::class, $standardSegment->getId()))->null();
    expect($this->entityManager->find(NewsletterSegmentEntity::class, $notificationHistorySegment->getId()))->null();

    // Newsletter stats notifications
    expect($this->entityManager->find(StatsNotificationEntity::class, $standardStatsNotificationScheduledTask->getId()))->null();
    expect($this->entityManager->find(StatsNotificationEntity::class, $notificationHistoryStatsNotification->getId()))->null();

    // Newsletter stats notifications scheduled tasks
    expect($this->entityManager->find(ScheduledTaskEntity::class, $standardStatsNotificationScheduledTask->getId()))->null();
    expect($this->entityManager->find(ScheduledTaskEntity::class, $notificationHistoryStatsNotificationScheduledTask->getId()))->null();

    // Newsletter links
    expect($this->entityManager->find(NewsletterLinkEntity::class, $standardLink->getId()))->null();
    expect($this->entityManager->find(NewsletterLinkEntity::class, $notificationHistoryLink->getId()))->null();

    // Option fields values
    expect($this->entityManager->find(NewsletterOptionEntity::class, $optionValue->getId()))->null();

    // Newsletter post
    expect($this->entityManager->find(NewsletterPostEntity::class, $newsletterPost->getId()))->null();

    // Statistics data
    expect($this->entityManager->find(StatisticsNewsletterEntity::class, $statisticsNewsletter->getId()))->null();
    expect($this->entityManager->find(StatisticsOpenEntity::class, $statisticsOpen->getId()))->null();
    expect($this->entityManager->find(StatisticsClickEntity::class, $statisticsClick->getId()))->null();
    expect($this->entityManager->find(StatisticsWooCommercePurchaseEntity::class, $statisticsPurchase->getId()))->null();
  }

  public function testItGetsArchiveNewslettersForSegments() {
    $types = [
      NewsletterEntity::TYPE_STANDARD,
      NewsletterEntity::TYPE_NOTIFICATION_HISTORY,
    ];

    list($newsletters) = $this->createNewslettersAndSendingTasks($types);

    // set segment association for the last newsletter
    $segment = new SegmentEntity('Segment', SegmentEntity::TYPE_DEFAULT, 'description');
    $this->entityManager->persist($segment);
    $newsletterSegment = new NewsletterSegmentEntity($newsletters[1], $segment);
    $this->entityManager->persist($newsletterSegment);
    $this->entityManager->flush();

    expect($this->repository->findAll())->count(2);

    // return archives in a given segment
    $results = $this->repository->getArchives([$segment->getId()]);

    expect($results)->count(1);
    expect($results[0]->getId())->equals($newsletters[1]->getId());
    expect($results[0]->getType())->equals(NewsletterEntity::TYPE_NOTIFICATION_HISTORY);
  }

  public function testItGetsAllArchiveNewsletters() {
    $types = [
      NewsletterEntity::TYPE_STANDARD,
      NewsletterEntity::TYPE_STANDARD, // should be returned
      NewsletterEntity::TYPE_WELCOME,
      NewsletterEntity::TYPE_AUTOMATIC,
      NewsletterEntity::TYPE_AUTOMATION,
      NewsletterEntity::TYPE_NOTIFICATION,
      NewsletterEntity::TYPE_NOTIFICATION_HISTORY, // should be returned
      NewsletterEntity::TYPE_NOTIFICATION_HISTORY,
    ];

    list($newsletters, $sendingQueues) = $this->createNewslettersAndSendingTasks($types);

    // set the sending queue status of the first newsletter to null
    $sendingQueues[0]->status = null;
    $sendingQueues[0]->save();

    // trash the last newsletter
    end($newsletters)->setDeletedAt(new Carbon());
    $this->entityManager->flush();

    expect($this->repository->findAll())->count(8);

    // archives return only:
    // 1. STANDARD and NOTIFICATION HISTORY newsletters
    // 2. active newsletters (i.e., not trashed)
    // 3. with sending queue records that are COMPLETED
    $results = $this->repository->getArchives();

    expect($results)->count(2);
    expect($results[0]->getId())->equals($newsletters[1]->getId());
    expect($results[0]->getType())->equals(NewsletterEntity::TYPE_STANDARD);
    expect($results[1]->getId())->equals($newsletters[6]->getId());
    expect($results[1]->getType())->equals(NewsletterEntity::TYPE_NOTIFICATION_HISTORY);
  }

  private function createNewsletter(string $type, string $status = NewsletterEntity::STATUS_DRAFT, $parent = null): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setType($type);
    $newsletter->setSubject('My Standard Newsletter');
    $newsletter->setBody(Fixtures::get('newsletter_body_template'));
    $newsletter->setStatus($status);
    $newsletter->setParent($parent);
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();
    return $newsletter;
  }

  private function createNewslettersAndSendingTasks(array $types): array {
    $newsletters = [];
    $sendingQueues = [];
    for ($i = 0; $i < count($types); $i++) {
      $newsletters[$i] = $this->createNewsletter($types[$i]);

      $sendingQueues[$i] = SendingTask::create();
      $sendingQueues[$i]->newsletter_id = $newsletters[$i]->getId();
      $sendingQueues[$i]->status = SendingQueueEntity::STATUS_COMPLETED;
      $sendingQueues[$i]->save();
    }

    return [$newsletters, $sendingQueues];
  }

  private function createQueueWithTaskAndSegmentAndSubscribers(NewsletterEntity $newsletter, $status = ScheduledTaskEntity::STATUS_SCHEDULED): SendingQueueEntity {
    $task = new ScheduledTaskEntity();
    $task->setType(SendingTask::TASK_TYPE);
    $task->setStatus($status);
    $this->entityManager->persist($task);

    $queue = new SendingQueueEntity();
    $queue->setNewsletter($newsletter);
    $queue->setTask($task);
    $this->entityManager->persist($queue);
    $newsletter->getQueues()->add($queue);

    $segment = new SegmentEntity("List for newsletter id {$newsletter->getId()}", SegmentEntity::TYPE_DEFAULT, 'Description');
    $this->entityManager->persist($segment);

    $subscriber = new SubscriberEntity();
    $subscriber->setEmail("sub{$newsletter->getId()}@mailpoet.com");
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    $scheduledTaskSubscriber = new ScheduledTaskSubscriberEntity($task, $subscriber);
    $this->entityManager->persist($scheduledTaskSubscriber);

    $newsletterSegment = new NewsletterSegmentEntity($newsletter, $segment);
    $newsletter->getNewsletterSegments()->add($newsletterSegment);
    $this->entityManager->persist($newsletterSegment);
    $this->entityManager->flush();
    return $queue;
  }

  private function createStatNotification(NewsletterEntity $newsletter): StatsNotificationEntity {
    $task = new ScheduledTaskEntity();
    $task->setType('stats_notification');
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->entityManager->persist($task);
    $statsNotification = new StatsNotificationEntity($newsletter, $task);
    $this->entityManager->persist($statsNotification);
    $this->entityManager->flush();
    return $statsNotification;
  }

  private function createNewsletterLink(NewsletterEntity $newsletter, SendingQueueEntity $queue): NewsletterLinkEntity {
    $link = new NewsletterLinkEntity($newsletter, $queue, 'http://example.com', 'abcd');
    $this->entityManager->persist($link);
    $this->entityManager->flush();
    return $link;
  }

  private function createNewsletterOptionField(string $newsletterType, string $name): NewsletterOptionFieldEntity {
    $newsletterOptionField = new NewsletterOptionFieldEntity();
    $newsletterOptionField->setNewsletterType($newsletterType);
    $newsletterOptionField->setName($name);
    $this->entityManager->persist($newsletterOptionField);
    $this->entityManager->flush();
    return $newsletterOptionField;
  }

  private function createNewsletterOption(NewsletterEntity $newsletter, NewsletterOptionFieldEntity $field, $value): NewsletterOptionEntity {
    $option = new NewsletterOptionEntity($newsletter, $field);
    $option->setValue($value);
    $this->entityManager->persist($option);
    $this->entityManager->flush();
    return $option;
  }

  private function createNewsletterPost(NewsletterEntity $newsletter, int $postId): NewsletterPostEntity {
    $post = new NewsletterPostEntity($newsletter, $postId);
    $this->entityManager->persist($post);
    $this->entityManager->flush();
    return $post;
  }

  private function createNewsletterStatistics(NewsletterEntity $newsletter, SendingQueueEntity $queue, SubscriberEntity $subscriber): StatisticsNewsletterEntity {
    $statisticsNewsletter = new StatisticsNewsletterEntity($newsletter, $queue, $subscriber);
    $this->entityManager->persist($statisticsNewsletter);
    $this->entityManager->flush();
    return $statisticsNewsletter;
  }

  private function createOpenStatistics(NewsletterEntity $newsletter, SendingQueueEntity $queue, SubscriberEntity $subscriber): StatisticsOpenEntity {
    $statistics = new StatisticsOpenEntity($newsletter, $queue, $subscriber);
    $this->entityManager->persist($statistics);
    $this->entityManager->flush();
    return $statistics;
  }

  private function createClickStatistics(
    NewsletterEntity $newsletter,
    SendingQueueEntity $queue,
    SubscriberEntity $subscriber,
    NewsletterLinkEntity $link
  ): StatisticsClickEntity {
    $statistics = new StatisticsClickEntity($newsletter, $queue, $subscriber, $link, 1);
    $this->entityManager->persist($statistics);
    $this->entityManager->flush();
    return $statistics;
  }

  private function createPurchaseStatistics(
    NewsletterEntity $newsletter,
    SendingQueueEntity $queue,
    StatisticsClickEntity $click,
    SubscriberEntity $subscriber
  ): StatisticsWooCommercePurchaseEntity {
    $statistics = new StatisticsWooCommercePurchaseEntity($newsletter, $queue, $click, 1, 'EUR', 100);
    $statistics->setSubscriber($subscriber);
    $this->entityManager->persist($statistics);
    $this->entityManager->flush();
    return $statistics;
  }
}
