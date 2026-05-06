<?php declare(strict_types = 1);

namespace MailPoet\Newsletter;

use Codeception\Util\Fixtures;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterSegmentEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\SendingQueue as SendingQueueFactory;
use MailPoetVendor\Carbon\Carbon;

class NewsletterRepositoryTest extends \MailPoetTest {
  /** @var NewslettersRepository */
  private $repository;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(NewslettersRepository::class);
  }

  public function testItGetsStandardAndAutomationNewsletterList() {
    $standardNewsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD, NewsletterEntity::STATUS_SENT);
    $automationEmail = $this->createNewsletter(NewsletterEntity::TYPE_AUTOMATION, NewsletterEntity::STATUS_ACTIVE);
    $automationTransactionalEmail = $this->createNewsletter(NewsletterEntity::TYPE_AUTOMATION_TRANSACTIONAL, NewsletterEntity::STATUS_ACTIVE);
    $automationDraftEmail = $this->createNewsletter(NewsletterEntity::TYPE_AUTOMATION, NewsletterEntity::STATUS_DRAFT);
    $notification = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION, NewsletterEntity::STATUS_ACTIVE);

    $newsletterIds = array_map(function(NewsletterEntity $newsletter) {
      return $newsletter->getId();
    }, $this->repository->getStandardAndAutomationNewsletterList());

    $this->assertContains($standardNewsletter->getId(), $newsletterIds);
    $this->assertContains($automationEmail->getId(), $newsletterIds);
    $this->assertContains($automationTransactionalEmail->getId(), $newsletterIds);
    $this->assertNotContains($automationDraftEmail->getId(), $newsletterIds);
    $this->assertNotContains($notification->getId(), $newsletterIds);
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
    verify($standardNewsletter->getDeletedAt())->notNull();
    verify($notification->getDeletedAt())->notNull();

    // Should trash sending queue and task
    $standardQueue = $standardNewsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $standardQueue);
    $this->entityManager->refresh($standardQueue);
    verify($standardQueue->getDeletedAt())->notNull();
    $scheduledTask = $standardQueue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $this->entityManager->refresh($scheduledTask);
    verify($scheduledTask->getDeletedAt())->notNull();

    // Should trash children + task + queue
    $this->entityManager->refresh($notificationHistory);
    verify($notificationHistory->getDeletedAt())->notNull();
    $notificationHistory = $notificationHistory->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $notificationHistory);
    $this->entityManager->refresh($notificationHistory);
    verify($notificationHistory->getDeletedAt())->notNull();
    $scheduledTask = $notificationHistory->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $this->entityManager->refresh($scheduledTask);
    verify($scheduledTask->getDeletedAt())->notNull();
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
    verify($standardNewsletter->getDeletedAt())->null();
    verify($notification->getDeletedAt())->null();
    verify($standardNewsletter->getStatus())->equals(NewsletterEntity::STATUS_SENDING);
    verify($notification->getStatus())->equals(NewsletterEntity::STATUS_ACTIVE);

    // Should restore sending queue and task
    $standardQueue = $standardNewsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $standardQueue);
    $this->entityManager->refresh($standardQueue);
    verify($standardQueue->getDeletedAt())->null();
    $scheduledTask = $standardQueue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $this->entityManager->refresh($scheduledTask);
    verify($scheduledTask->getDeletedAt())->null();
    // Pause sending tasks which were in progress
    verify($scheduledTask->getStatus())->equals(ScheduledTaskEntity::STATUS_PAUSED);

    // Should restore children + task + queue
    $this->entityManager->refresh($notificationHistory);
    verify($notificationHistory->getDeletedAt())->null();
    $notificationHistoryQueue = $notificationHistory->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $notificationHistoryQueue);
    $this->entityManager->refresh($notificationHistoryQueue);
    verify($notificationHistoryQueue->getDeletedAt())->null();
    $scheduledTask = $notificationHistoryQueue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $this->entityManager->refresh($scheduledTask);
    verify($scheduledTask->getDeletedAt())->null();
    verify($scheduledTask->getStatus())->equals(ScheduledTaskEntity::STATUS_SCHEDULED);
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

    verify($this->repository->findAll())->arrayCount(2);

    // return archives in a given segment
    $results = $this->repository->getArchives(['segmentIds' => [$segment->getId()]]);

    verify($results)->arrayCount(1);
    verify($results[0]->getId())->equals($newsletters[1]->getId());
    verify($results[0]->getType())->equals(NewsletterEntity::TYPE_NOTIFICATION_HISTORY);
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

    list($newsletters, $scheduledTasks) = $this->createNewslettersAndSendingTasks($types);

    // set the sending queue status of the first newsletter to null
    $scheduledTasks[0]->setStatus(null);
    $this->entityManager->persist($scheduledTasks[0]);

    // trash the last newsletter
    end($newsletters)->setDeletedAt(new Carbon());
    $this->entityManager->flush();

    verify($this->repository->findAll())->arrayCount(8);

    // archives return only:
    // 1. STANDARD and NOTIFICATION HISTORY newsletters
    // 2. active newsletters (i.e., not trashed)
    // 3. with sending queue records that are COMPLETED
    $results = $this->repository->getArchives();

    verify($results)->arrayCount(2);
    verify($results[0]->getId())->equals($newsletters[1]->getId());
    verify($results[0]->getType())->equals(NewsletterEntity::TYPE_STANDARD);
    verify($results[1]->getId())->equals($newsletters[6]->getId());
    verify($results[1]->getType())->equals(NewsletterEntity::TYPE_NOTIFICATION_HISTORY);
  }

  public function testFindStuckPostNotificationParentsReturnsOneRowPerParent() {
    $parentPaused = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION, NewsletterEntity::STATUS_ACTIVE);
    // Multiple stuck histories under the same parent should collapse to one row
    $this->createNotificationHistoryWithTaskStatus($parentPaused, ScheduledTaskEntity::STATUS_PAUSED);
    $this->createNotificationHistoryWithTaskStatus($parentPaused, ScheduledTaskEntity::STATUS_PAUSED);
    $this->createNotificationHistoryWithTaskStatus($parentPaused, ScheduledTaskEntity::STATUS_PAUSED);

    $parentInvalid = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION, NewsletterEntity::STATUS_ACTIVE);
    $this->createNotificationHistoryWithTaskStatus($parentInvalid, ScheduledTaskEntity::STATUS_INVALID);

    // A parent with both paused and invalid histories should be flagged as invalid (more severe)
    $parentMixed = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION, NewsletterEntity::STATUS_ACTIVE);
    $this->createNotificationHistoryWithTaskStatus($parentMixed, ScheduledTaskEntity::STATUS_PAUSED);
    $this->createNotificationHistoryWithTaskStatus($parentMixed, ScheduledTaskEntity::STATUS_INVALID);

    // Healthy parent: still running, completed, not-sending — must not appear
    $parentHealthy = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION, NewsletterEntity::STATUS_ACTIVE);
    $this->createNotificationHistoryWithTaskStatus($parentHealthy, null);
    $this->createNotificationHistoryWithTaskStatus($parentHealthy, ScheduledTaskEntity::STATUS_COMPLETED);
    $draft = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, NewsletterEntity::STATUS_DRAFT, $parentHealthy);
    $this->createQueueWithTaskAndSegmentAndSubscribers($draft, ScheduledTaskEntity::STATUS_PAUSED);

    $results = $this->repository->findStuckPostNotificationParents();

    verify($results)->arrayCount(3);

    $byParentId = [];
    foreach ($results as $row) {
      $parentId = $row['parent']->getId();
      $this->assertNotNull($parentId);
      $byParentId[$parentId] = $row['hasInvalid'];
    }

    $parentPausedId = (int)$parentPaused->getId();
    $parentInvalidId = (int)$parentInvalid->getId();
    $parentMixedId = (int)$parentMixed->getId();
    $parentHealthyId = (int)$parentHealthy->getId();

    verify(isset($byParentId[$parentPausedId]))->true();
    verify($byParentId[$parentPausedId])->false();

    verify(isset($byParentId[$parentInvalidId]))->true();
    verify($byParentId[$parentInvalidId])->true();

    verify(isset($byParentId[$parentMixedId]))->true();
    verify($byParentId[$parentMixedId])->true();

    verify(isset($byParentId[$parentHealthyId]))->false();
  }

  public function testFindStuckPostNotificationParentsExcludesTrashedEntries() {
    $parent = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION, NewsletterEntity::STATUS_ACTIVE);
    $trashedHistory = $this->createNotificationHistoryWithTaskStatus($parent, ScheduledTaskEntity::STATUS_PAUSED);
    $trashedHistory->setDeletedAt(new Carbon());
    $this->entityManager->flush();

    $trashedParent = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION, NewsletterEntity::STATUS_ACTIVE);
    $trashedParent->setDeletedAt(new Carbon());
    $this->createNotificationHistoryWithTaskStatus($trashedParent, ScheduledTaskEntity::STATUS_PAUSED);
    $this->entityManager->flush();

    verify($this->repository->findStuckPostNotificationParents())->arrayCount(0);
  }

  public function testFindStuckPostNotificationParentsRespectsLimitAcrossDistinctParents() {
    // One noisy parent with many stuck histories — must not consume the limit
    $noisyParent = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION, NewsletterEntity::STATUS_ACTIVE);
    for ($i = 0; $i < 4; $i++) {
      $this->createNotificationHistoryWithTaskStatus($noisyParent, ScheduledTaskEntity::STATUS_PAUSED);
    }

    // Three other distinct stuck parents
    $otherParents = [];
    for ($i = 0; $i < 3; $i++) {
      $other = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION, NewsletterEntity::STATUS_ACTIVE);
      $this->createNotificationHistoryWithTaskStatus($other, ScheduledTaskEntity::STATUS_PAUSED);
      $otherParents[] = $other->getId();
    }

    $results = $this->repository->findStuckPostNotificationParents(2);
    verify($results)->arrayCount(2);

    $resultParentIds = array_map(fn(array $row) => $row['parent']->getId(), $results);
    verify(count(array_unique($resultParentIds)))->equals(2);
  }

  private function createNotificationHistoryWithTaskStatus(NewsletterEntity $parent, ?string $taskStatus): NewsletterEntity {
    $history = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, NewsletterEntity::STATUS_SENDING, $parent);
    $this->createQueueWithTaskAndSegmentAndSubscribers($history, $taskStatus);
    return $history;
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
    $scheduledTasks = [];
    for ($i = 0; $i < count($types); $i++) {
      $newsletters[$i] = $this->createNewsletter($types[$i]);

      $scheduledTasks[$i] = (new ScheduledTaskFactory())->create(SendingQueue::TASK_TYPE, SendingQueueEntity::STATUS_COMPLETED);
      (new SendingQueueFactory())->create($scheduledTasks[$i], $newsletters[$i]);
    }

    return [$newsletters, $scheduledTasks];
  }

  private function createQueueWithTaskAndSegmentAndSubscribers(NewsletterEntity $newsletter, $status = ScheduledTaskEntity::STATUS_SCHEDULED): SendingQueueEntity {
    $task = new ScheduledTaskEntity();
    $task->setType(SendingQueue::TASK_TYPE);
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
}
