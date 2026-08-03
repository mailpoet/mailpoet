<?php declare(strict_types = 1);

namespace MailPoet\Util\DataInconsistency;

use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberTagEntity;
use MailPoet\Entities\TagEntity;
use MailPoet\Test\DataFactories\CustomField;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\NewsletterLink;
use MailPoet\Test\DataFactories\NewsletterPost;
use MailPoet\Test\DataFactories\ScheduledTask;
use MailPoet\Test\DataFactories\ScheduledTaskSubscriber;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\SendingQueue;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoet\Test\DataFactories\Tag;

class DataInconsistencyRepositoryTest extends \MailPoetTest {
  private DataInconsistencyRepository $repository;

  public function _before(): void {
    $this->repository = $this->diContainer->get(DataInconsistencyRepository::class);
  }

  public function testItFetchesOrphanedSendingTasksCount(): void {
    $orphanedSendingTasksCount = $this->repository->getOrphanedSendingTasksCount();
    verify($orphanedSendingTasksCount)->equals(0);

    // Add non orphaned sending task
    $okTask = (new ScheduledTask())->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    (new SendingQueue())->create($okTask);
    $orphanedSendingTasksCount = $this->repository->getOrphanedSendingTasksCount();
    verify($orphanedSendingTasksCount)->equals(0);

    // Add orphaned sending tasks
    (new ScheduledTask())->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    (new ScheduledTask())->create(SendingQueueWorker::TASK_TYPE, null);
    $orphanedSendingTasksCount = $this->repository->getOrphanedSendingTasksCount();
    verify($orphanedSendingTasksCount)->equals(2);
  }

  public function testItFetchesSendingQueuesWithoutTaskCount(): void {
    verify($this->repository->getSendingQueuesWithoutTaskCount())->equals(0);

    $okTask = (new ScheduledTask())->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED);
    (new SendingQueue())->create($okTask);
    verify($this->repository->getSendingQueuesWithoutTaskCount())->equals(0);

    $taskToDelete = (new ScheduledTask())->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED);
    $queue = (new SendingQueue())->create($taskToDelete);
    $this->entityManager->createQueryBuilder()
      ->delete(ScheduledTaskEntity::class, 't')
      ->where('t.id = :id')
      ->setParameter('id', $taskToDelete->getId())
      ->getQuery()
      ->execute();

    verify($this->repository->getSendingQueuesWithoutTaskCount())->equals(1);
    // the queue itself is left alone, it holds the only record of the send
    $this->assertNotNull($this->entityManager->find(SendingQueueEntity::class, $queue->getId()));
  }

  public function testItCleansUpOrphanedSendingTasks(): void {
    $taskWithSubscriber = (new ScheduledTask())->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    (new ScheduledTask())->create(SendingQueueWorker::TASK_TYPE, null);

    $subscriber = (new Subscriber())->create();
    (new ScheduledTaskSubscriber())->createProcessed($taskWithSubscriber, $subscriber);

    $orphanedSendingTasksCount = $this->repository->getOrphanedSendingTasksCount();
    verify($orphanedSendingTasksCount)->equals(2);
    $taskSubscriberCount = $this->entityManager->getRepository(ScheduledTaskSubscriberEntity::class)->count([]);
    verify($taskSubscriberCount)->equals(1);

    $this->repository->cleanupOrphanedSendingTasks();
    $orphanedSendingTasksCount = $this->repository->getOrphanedSendingTasksCount();
    verify($orphanedSendingTasksCount)->equals(0);

    // Check subscriber is not deleted
    $this->entityManager->detach($subscriber);
    $subscriber = $this->entityManager->find(SubscriberEntity::class, $subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);

    // Check task subscriber is deleted
    $taskSubscriberCount = $this->entityManager->getRepository(ScheduledTaskSubscriberEntity::class)->count([]);
    verify($taskSubscriberCount)->equals(0);
  }

  public function testItHandlesOrphanedScheduledTaskSubscribers(): void {
    $taskToDelete = (new ScheduledTask())->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    $taskToKeep = (new ScheduledTask())->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);

    $subscriber1 = (new Subscriber())->create();
    (new ScheduledTaskSubscriber())->createProcessed($taskToDelete, $subscriber1);
    (new ScheduledTaskSubscriber())->createProcessed($taskToKeep, $subscriber1);
    $subscriber2 = (new Subscriber())->create();
    (new ScheduledTaskSubscriber())->createProcessed($taskToDelete, $subscriber2);
    (new ScheduledTaskSubscriber())->createProcessed($taskToKeep, $subscriber2);

    $this->entityManager->remove($taskToDelete);
    $this->entityManager->flush();

    $taskSubscriberCount = $this->entityManager->getRepository(ScheduledTaskSubscriberEntity::class)->count([]);
    verify($taskSubscriberCount)->equals(4);

    verify($this->repository->getOrphanedScheduledTasksSubscribersCount())->equals(2);
    $this->repository->cleanupOrphanedScheduledTaskSubscribers();
    verify($this->repository->getOrphanedScheduledTasksSubscribersCount())->equals(0);

    // We keep the task and subscriber that was associated with the task we kept
    $taskSubscriberCount = $this->entityManager->getRepository(ScheduledTaskSubscriberEntity::class)->count([]);
    verify($taskSubscriberCount)->equals(2);
  }

  public function testItHandlesSendingQueuesWithoutNewsletter(): void {
    $newsletter = (new Newsletter())->create();
    $taskWithSubscriber = (new ScheduledTask())->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);

    $subscriber1 = (new Subscriber())->create();
    (new ScheduledTaskSubscriber())->createProcessed($taskWithSubscriber, $subscriber1);
    $subscriber2 = (new Subscriber())->create();
    (new ScheduledTaskSubscriber())->createProcessed($taskWithSubscriber, $subscriber2);

    (new SendingQueue())->create($taskWithSubscriber, $newsletter);

    $this->entityManager->remove($newsletter);
    $this->entityManager->flush();

    verify($this->repository->getSendingQueuesWithoutNewsletterCount())->equals(1);
    $this->repository->cleanupSendingQueuesWithoutNewsletter();
    verify($this->repository->getSendingQueuesWithoutNewsletterCount())->equals(0);
    verify($this->repository->getOrphanedSendingTasksCount())->equals(0);
    verify($this->repository->getOrphanedScheduledTasksSubscribersCount())->equals(0);
  }

  public function testItHandlesOrphanedSubscriptions(): void {
    $segmentToDelete = (new Segment())->create();
    $segmentToKeep = (new Segment())->create();

    $subscriberToDelete = (new Subscriber())->withSegments([$segmentToDelete, $segmentToKeep])->create();
    $subscriberToKeep = (new Subscriber())->withSegments([$segmentToDelete, $segmentToKeep])->create();

    $subscriberTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $this->entityManager->getConnection()
      ->executeQuery("DELETE s FROM $subscriberTable s  WHERE id = :id", ['id' => $subscriberToDelete->getId()]);

    $segmentTable = $this->entityManager->getClassMetadata(SegmentEntity::class)->getTableName();
    $this->entityManager->getConnection()
      ->executeQuery("DELETE s FROM $segmentTable s  WHERE id = :id", ['id' => $segmentToDelete->getId()]);

    // Expect 3 because both subscribers were associated to the deleted segment + deleted subscriber to segment we kept
    verify($this->repository->getOrphanedSubscriptionsCount())->equals(3);
    $this->repository->cleanupOrphanedSubscriptions();
    verify($this->repository->getOrphanedSubscriptionsCount())->equals(0);

    $this->entityManager->detach($subscriberToKeep);
    $subscriberToKeep = $this->entityManager->find(SubscriberEntity::class, $subscriberToKeep->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriberToKeep);

    $this->entityManager->detach($segmentToKeep);
    $segmentToKeep = $this->entityManager->find(SegmentEntity::class, $segmentToKeep->getId());
    $this->assertInstanceOf(SegmentEntity::class, $segmentToKeep);
  }

  public function testItHandlesOrphanedSubscriberCustomFields(): void {
    $customFieldToDelete = (new CustomField())->create();
    $customFieldToKeep = (new CustomField())->create();
    $subscriberToDelete = (new Subscriber())->create();
    $subscriberToKeep = (new Subscriber())->create();

    // Orphaned via subscriber, orphaned via custom field, and a healthy row.
    $this->createSubscriberCustomField($subscriberToDelete, $customFieldToKeep);
    $this->createSubscriberCustomField($subscriberToKeep, $customFieldToDelete);
    $this->createSubscriberCustomField($subscriberToKeep, $customFieldToKeep);

    $subscriberTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $this->entityManager->getConnection()
      ->executeStatement("DELETE FROM $subscriberTable WHERE id = :id", ['id' => $subscriberToDelete->getId()]);
    $customFieldTable = $this->entityManager->getClassMetadata(CustomFieldEntity::class)->getTableName();
    $this->entityManager->getConnection()
      ->executeStatement("DELETE FROM $customFieldTable WHERE id = :id", ['id' => $customFieldToDelete->getId()]);

    verify($this->repository->getOrphanedSubscriberCustomFieldsCount())->equals(2);
    $this->repository->cleanupOrphanedSubscriberCustomFields();
    verify($this->repository->getOrphanedSubscriberCustomFieldsCount())->equals(0);

    // The healthy row is untouched.
    verify($this->entityManager->getRepository(SubscriberCustomFieldEntity::class)->count([]))->equals(1);
  }

  public function testItHandlesOrphanedSubscriberTags(): void {
    $tagToDelete = (new Tag())->create();
    $tagToKeep = (new Tag())->create();
    $subscriberToDelete = (new Subscriber())->create();
    $subscriberToKeep = (new Subscriber())->create();

    // Orphaned via subscriber, orphaned via tag, and a healthy row.
    $this->createSubscriberTag($subscriberToDelete, $tagToKeep);
    $this->createSubscriberTag($subscriberToKeep, $tagToDelete);
    $this->createSubscriberTag($subscriberToKeep, $tagToKeep);

    $subscriberTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $this->entityManager->getConnection()
      ->executeStatement("DELETE FROM $subscriberTable WHERE id = :id", ['id' => $subscriberToDelete->getId()]);
    $tagTable = $this->entityManager->getClassMetadata(TagEntity::class)->getTableName();
    $this->entityManager->getConnection()
      ->executeStatement("DELETE FROM $tagTable WHERE id = :id", ['id' => $tagToDelete->getId()]);

    verify($this->repository->getOrphanedSubscriberTagsCount())->equals(2);
    $this->repository->cleanupOrphanedSubscriberTags();
    verify($this->repository->getOrphanedSubscriberTagsCount())->equals(0);

    // The healthy row is untouched.
    verify($this->entityManager->getRepository(SubscriberTagEntity::class)->count([]))->equals(1);
  }

  public function testItHandlesOrphanedLinks(): void {
    $newsletterToDelete = (new Newsletter())->create();
    $task1 = (new ScheduledTask())->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    (new SendingQueue())->create($task1, $newsletterToDelete);
    $this->entityManager->refresh($newsletterToDelete);

    $newsletterToKeep = (new Newsletter())->create();
    $task2 = (new ScheduledTask())->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    $queueToDelete = (new SendingQueue())->create($task2, $newsletterToKeep);
    $this->entityManager->refresh($newsletterToKeep);

    $newsletterToKeep2 = (new Newsletter())->create();
    $task3 = (new ScheduledTask())->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    (new SendingQueue())->create($task3, $newsletterToKeep2);
    $this->entityManager->refresh($newsletterToKeep2);

    (new NewsletterLink($newsletterToDelete))->create();
    (new NewsletterLink($newsletterToKeep))->create();
    (new NewsletterLink($newsletterToKeep2))->create();

    $this->entityManager->remove($newsletterToDelete);
    $this->entityManager->remove($queueToDelete);
    $this->entityManager->flush();

    verify($this->repository->getOrphanedNewsletterLinksCount())->equals(2);
    $this->repository->cleanupOrphanedNewsletterLinks();
    verify($this->repository->getOrphanedNewsletterLinksCount())->equals(0);
  }

  public function testItHandlesOrphanedNewsletterPosts(): void {
    $newsletterToDelete = (new Newsletter())->create();
    $newsletterToKeep = (new Newsletter())->create();
    (new NewsletterPost($newsletterToDelete))->create();
    (new NewsletterPost($newsletterToKeep))->create();

    $this->entityManager->remove($newsletterToDelete);
    $this->entityManager->flush();

    verify($this->repository->getOrphanedNewsletterPostsCount())->equals(1);
    $this->repository->cleanupOrphanedNewsletterPosts();
    verify($this->repository->getOrphanedNewsletterPostsCount())->equals(0);
  }

  private function createSubscriberCustomField(SubscriberEntity $subscriber, CustomFieldEntity $customField): void {
    $subscriberCustomField = new SubscriberCustomFieldEntity($subscriber, $customField, 'some value');
    $this->entityManager->persist($subscriberCustomField);
    $this->entityManager->flush();
  }

  private function createSubscriberTag(SubscriberEntity $subscriber, TagEntity $tag): void {
    $subscriberTag = new SubscriberTagEntity($tag, $subscriber);
    $this->entityManager->persist($subscriberTag);
    $this->entityManager->flush();
  }
}
