<?php declare(strict_types = 1);

namespace MailPoet\Util\DataInconsistency;

use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\ScheduledTask;
use MailPoet\Test\DataFactories\ScheduledTaskSubscriber;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\SendingQueue;
use MailPoet\Test\DataFactories\Subscriber;

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
    $taskWithSubscriber = (new ScheduledTask())->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);

    $subscriber1 = (new Subscriber())->create();
    (new ScheduledTaskSubscriber())->createProcessed($taskWithSubscriber, $subscriber1);
    $subscriber2 = (new Subscriber())->create();
    (new ScheduledTaskSubscriber())->createProcessed($taskWithSubscriber, $subscriber2);

    $this->entityManager->remove($taskWithSubscriber);
    $this->entityManager->flush();

    verify($this->repository->getOrphanedScheduledTasksSubscribersCount())->equals(2);
    $this->repository->cleanupOrphanedScheduledTaskSubscribers();
    verify($this->repository->getOrphanedScheduledTasksSubscribersCount())->equals(0);
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
}
