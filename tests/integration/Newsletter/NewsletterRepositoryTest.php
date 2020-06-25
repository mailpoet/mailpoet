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

class NewsletterRepositoryTest extends \MailPoetTest {
  /** @var NewslettersRepository */
  private $repository;

  /** @var ScheduledTaskSubscribersRepository */
  private $taskSubscribersRepository;

  public function _before() {
    parent::_before();
    $this->cleanup();
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
    assert($standardQueue instanceof SendingQueueEntity);
    $this->entityManager->refresh($standardQueue);
    expect($standardQueue->getDeletedAt())->notNull();
    $scheduledTask = $standardQueue->getTask();
    assert($scheduledTask instanceof ScheduledTaskEntity);
    $this->entityManager->refresh($scheduledTask);
    expect($scheduledTask->getDeletedAt())->notNull();

    // Should trash children + task + queue
    $this->entityManager->refresh($notificationHistory);
    expect($notificationHistory->getDeletedAt())->notNull();
    $notificationHistory = $notificationHistory->getLatestQueue();
    assert($notificationHistory instanceof SendingQueueEntity);
    $this->entityManager->refresh($notificationHistory);
    expect($notificationHistory->getDeletedAt())->notNull();
    $scheduledTask = $notificationHistory->getTask();
    assert($scheduledTask instanceof ScheduledTaskEntity);
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
    assert($standardQueue instanceof SendingQueueEntity);
    $this->entityManager->refresh($standardQueue);
    expect($standardQueue->getDeletedAt())->null();
    $scheduledTask = $standardQueue->getTask();
    assert($scheduledTask instanceof ScheduledTaskEntity);
    $this->entityManager->refresh($scheduledTask);
    expect($scheduledTask->getDeletedAt())->null();
    // Pause sending tasks which were in progress
    expect($scheduledTask->getStatus())->equals(ScheduledTaskEntity::STATUS_PAUSED);

    // Should restore children + task + queue
    $this->entityManager->refresh($notificationHistory);
    expect($notificationHistory->getDeletedAt())->null();
    $notificationHistoryQueue = $notificationHistory->getLatestQueue();
    assert($notificationHistoryQueue instanceof SendingQueueEntity);
    $this->entityManager->refresh($notificationHistoryQueue);
    expect($notificationHistoryQueue->getDeletedAt())->null();
    $scheduledTask = $notificationHistoryQueue->getTask();
    assert($scheduledTask instanceof ScheduledTaskEntity);
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
    assert($standardSegment instanceof NewsletterSegmentEntity);
    $standardScheduledTaks = $standardQueue->getTask();
    assert($standardScheduledTaks instanceof ScheduledTaskEntity);
    $standardScheduledTaskSubscriber = $this->taskSubscribersRepository->findOneBy(['task' => $standardScheduledTaks]);
    assert($standardScheduledTaskSubscriber instanceof ScheduledTaskSubscriberEntity);
    $notificationHistoryScheduledTask = $notificationHistoryQueue->getTask();
    assert($notificationHistoryScheduledTask instanceof ScheduledTaskEntity);
    $notificationHistorySegment = $notificationHistory->getNewsletterSegments()->first();
    assert($notificationHistorySegment instanceof NewsletterSegmentEntity);
    $notificationHistoryScheduledTaskSubscriber = $this->taskSubscribersRepository->findOneBy(['task' => $notificationHistoryScheduledTask]);
    assert($notificationHistoryScheduledTaskSubscriber instanceof ScheduledTaskSubscriberEntity);
    $standardStatsNotification = $this->createStatNotification($standardNewsletter);
    $standardStatsNotificationScheduledTask = $standardStatsNotification->getTask();
    assert($standardStatsNotificationScheduledTask instanceof ScheduledTaskEntity);
    $notificationHistoryStatsNotification = $this->createStatNotification($notificationHistory);
    $notificationHistoryStatsNotificationScheduledTask = $notificationHistoryStatsNotification->getTask();
    assert($notificationHistoryStatsNotificationScheduledTask instanceof ScheduledTaskEntity);
    $standardLink = $this->createNewsletterLink($standardNewsletter, $standardQueue);
    $notificationHistoryLink = $this->createNewsletterLink($notificationHistory, $notificationHistoryQueue);
    $optionField = $this->createNewsletterOptionField(NewsletterEntity::TYPE_NOTIFICATION, 'option');
    $optionValue = $this->createNewsletterOption($notificationHistory, $optionField, 'value');
    $newsletterPost = $this->createNewsletterPost($notification, 1);

    $subscriber = $standardScheduledTaskSubscriber->getSubscriber();
    assert($subscriber instanceof SubscriberEntity);
    $statisticsNewsletter = $this->createNewsletterStatistics($standardNewsletter, $standardQueue, $subscriber);
    $statisticsOpen = $this->createOpenStatistics($standardNewsletter, $standardQueue, $subscriber);
    $statisticsClick = $this->createClickStatistics($standardNewsletter, $standardQueue, $subscriber, $standardLink);
    $statisticsPurchase = $this->createPurchaseStatistics($standardNewsletter, $standardQueue, $statisticsClick, $subscriber);

    // Trash
    $this->repository->bulkTrash([$standardNewsletter->getId(), $notification->getId()]);
    // Delete
    $this->repository->bulkDelete([$standardNewsletter->getId(), $notification->getId()]);

    // Detach entities so that ORM forget them
    $this->entityManager->detach($standardNewsletter);
    $this->entityManager->detach($notification);
    $this->entityManager->detach($notificationHistory);
    $this->entityManager->detach($standardQueue);
    $this->entityManager->detach($notificationHistoryQueue);
    $this->entityManager->detach($standardScheduledTaks);
    $this->entityManager->detach($notificationHistoryScheduledTask);
    $this->entityManager->detach($standardSegment);
    $this->entityManager->detach($notificationHistorySegment);
    $this->entityManager->detach($standardStatsNotification);
    $this->entityManager->detach($standardStatsNotificationScheduledTask);
    $this->entityManager->detach($notificationHistoryStatsNotification);
    $this->entityManager->detach($notificationHistoryStatsNotificationScheduledTask);
    $this->entityManager->detach($standardLink);
    $this->entityManager->detach($notificationHistoryLink);
    $this->entityManager->detach($optionValue);
    $this->entityManager->detach($newsletterPost);
    $this->entityManager->detach($statisticsNewsletter);
    $this->entityManager->detach($statisticsOpen);
    $this->entityManager->detach($statisticsClick);
    $this->entityManager->detach($statisticsPurchase);

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

  public function _after() {
    $this->cleanup();
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

    $segment = new SegmentEntity();
    $segment->setType(SegmentEntity::TYPE_DEFAULT);
    $segment->setName(" List for newsletter id {$newsletter->getId()}");
    $segment->setDescription('');
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
    $statistics = new StatisticsOpenEntity($newsletter, $queue, (int)$subscriber->getId());
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
    $statistics = new StatisticsClickEntity($newsletter, $queue, (int)$subscriber->getId(), $link, 1);
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

  private function cleanup() {
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
    $this->truncateEntity(NewsletterSegmentEntity::class);
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(ScheduledTaskSubscriberEntity::class);
    $this->truncateEntity(StatsNotificationEntity::class);
    $this->truncateEntity(NewsletterLinkEntity::class);
    $this->truncateEntity(NewsletterOptionFieldEntity::class);
    $this->truncateEntity(NewsletterOptionEntity::class);
    $this->truncateEntity(NewsletterPostEntity::class);
    $this->truncateEntity(StatisticsWooCommercePurchaseEntity::class);
    $this->truncateEntity(StatisticsOpenEntity::class);
    $this->truncateEntity(StatisticsClickEntity::class);
    $this->truncateEntity(StatisticsNewsletterEntity::class);
  }
}
