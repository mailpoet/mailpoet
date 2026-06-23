<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskQueuedSubscriberEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Carbon\Carbon;

class ScheduledTaskQueuedSubscriberRepositoryTest extends \MailPoetTest {
  private ScheduledTaskQueuedSubscriberRepository $repository;

  private ScheduledTaskFactory $scheduledTaskFactory;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(ScheduledTaskQueuedSubscriberRepository::class);
    $this->scheduledTaskFactory = new ScheduledTaskFactory();
  }

  public function testItEnqueuesSubscribedNonDeletedSubscribersAndDeduplicates(): void {
    $task = $this->createTask();
    $eligibleSubscriber1 = $this->createSubscriber();
    $eligibleSubscriber2 = $this->createSubscriber();
    $unsubscribedSubscriber = $this->createSubscriber(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $deletedSubscriber = $this->createSubscriber(SubscriberEntity::STATUS_SUBSCRIBED, true);

    $inserted = $this->repository->addSubscribersByIds($task, [
      (int)$eligibleSubscriber2->getId(),
      (int)$unsubscribedSubscriber->getId(),
      (int)$deletedSubscriber->getId(),
      (int)$eligibleSubscriber1->getId(),
      (int)$eligibleSubscriber2->getId(),
    ]);

    $this->assertSame(2, $inserted);
    $this->assertSame(0, $this->repository->addSubscribersByIds($task, [
      (int)$eligibleSubscriber1->getId(),
      (int)$eligibleSubscriber2->getId(),
    ]));
    $this->assertSame([
      (int)$eligibleSubscriber1->getId(),
      (int)$eligibleSubscriber2->getId(),
    ], $this->repository->getSubscriberIdsBatchForTask((int)$task->getId(), 0, 10));

    $queuedSubscribers = $this->repository->findBy(['task' => $task]);
    $this->assertCount(2, $queuedSubscribers);
    $this->assertNotNull($queuedSubscribers[0]->getCreatedAt());
  }

  public function testItReturnsSubscriberIdsBatchInAscendingOrderAfterCursor(): void {
    $task = $this->createTask();
    $subscriber1 = $this->createSubscriber();
    $subscriber2 = $this->createSubscriber();
    $subscriber3 = $this->createSubscriber();
    $subscriber4 = $this->createSubscriber();

    $subscriberIds = [
      (int)$subscriber1->getId(),
      (int)$subscriber2->getId(),
      (int)$subscriber3->getId(),
      (int)$subscriber4->getId(),
    ];
    $this->repository->addSubscribersByIds($task, [
      $subscriberIds[2],
      $subscriberIds[0],
      $subscriberIds[3],
      $subscriberIds[1],
    ]);

    $this->assertSame(
      [$subscriberIds[0], $subscriberIds[1]],
      $this->repository->getSubscriberIdsBatchForTask((int)$task->getId(), 0, 2)
    );
    $this->assertSame(
      [$subscriberIds[2], $subscriberIds[3]],
      $this->repository->getSubscriberIdsBatchForTask((int)$task->getId(), $subscriberIds[1], 10)
    );
    $this->assertSame(
      [$subscriberIds[3]],
      $this->repository->getSubscriberIdsBatchForTask((int)$task->getId(), $subscriberIds[2], 1)
    );
  }

  public function testItChecksWhetherTaskHasUnprocessedSubscribers(): void {
    $task = $this->createTask();

    $this->assertFalse($this->repository->hasUnprocessed($task));

    $queuedSubscriber = $this->createQueuedSubscriber($task);
    $this->assertTrue($this->repository->hasUnprocessed($task));

    $this->repository->deleteByScheduledTaskAndSubscriberIds($task, [(int)$queuedSubscriber->getSubscriberId()]);
    $this->assertFalse($this->repository->hasUnprocessed($task));
  }

  public function testItCountsSubscribersForTask(): void {
    $task1 = $this->createTask();
    $task2 = $this->createTask();

    $this->createQueuedSubscriber($task1);
    $this->createQueuedSubscriber($task1);
    $this->createQueuedSubscriber($task2);

    $this->assertSame(2, $this->repository->countForTask($task1));
    $this->assertSame(1, $this->repository->countForTask($task2));
  }

  public function testItDeletesSubscribersByScheduledTask(): void {
    $task1 = $this->createTask();
    $task2 = $this->createTask();
    $task1Subscriber1 = $this->createQueuedSubscriber($task1);
    $task1Subscriber2 = $this->createQueuedSubscriber($task1);
    $task2Subscriber = $this->createQueuedSubscriber($task2);

    $this->repository->deleteByScheduledTask($task1);

    $this->assertSame(0, $this->repository->countForTask($task1));
    $this->assertSame(1, $this->repository->countForTask($task2));
    $this->assertFalse($this->entityManager->contains($task1Subscriber1));
    $this->assertFalse($this->entityManager->contains($task1Subscriber2));
    $this->assertTrue($this->entityManager->contains($task2Subscriber));
  }

  public function testItDeletesSubscribersByScheduledTaskAndSubscriberIds(): void {
    $task1 = $this->createTask();
    $task2 = $this->createTask();
    $task1Subscriber1 = $this->createQueuedSubscriber($task1);
    $task1Subscriber2 = $this->createQueuedSubscriber($task1);
    $task2Subscriber = $this->createQueuedSubscriber($task2);

    $this->repository->deleteByScheduledTaskAndSubscriberIds($task1, [(int)$task1Subscriber1->getSubscriberId()]);

    $this->assertSame(1, $this->repository->countForTask($task1));
    $this->assertSame(1, $this->repository->countForTask($task2));
    $this->assertFalse($this->entityManager->contains($task1Subscriber1));
    $this->assertTrue($this->entityManager->contains($task1Subscriber2));
    $this->assertTrue($this->entityManager->contains($task2Subscriber));
  }

  public function testItDeletesSubscribersByTaskIds(): void {
    $task1 = $this->createTask();
    $task2 = $this->createTask();
    $task3 = $this->createTask();
    $task1Subscriber = $this->createQueuedSubscriber($task1);
    $task2Subscriber = $this->createQueuedSubscriber($task2);
    $task3Subscriber = $this->createQueuedSubscriber($task3);

    $this->repository->deleteByTaskIds([(int)$task1->getId(), (int)$task2->getId()]);

    $this->assertSame(0, $this->repository->countForTask($task1));
    $this->assertSame(0, $this->repository->countForTask($task2));
    $this->assertSame(1, $this->repository->countForTask($task3));
    $this->assertFalse($this->entityManager->contains($task1Subscriber));
    $this->assertFalse($this->entityManager->contains($task2Subscriber));
    $this->assertTrue($this->entityManager->contains($task3Subscriber));
  }

  private function createTask(): ScheduledTaskEntity {
    return $this->scheduledTaskFactory->create('sending', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->subDay());
  }

  private function createSubscriber(string $status = SubscriberEntity::STATUS_SUBSCRIBED, bool $deleted = false): SubscriberEntity {
    $subscriberFactory = (new SubscriberFactory())->withStatus($status);
    if ($deleted) {
      $subscriberFactory->withDeletedAt(Carbon::now());
    }
    return $subscriberFactory->create();
  }

  private function createQueuedSubscriber(ScheduledTaskEntity $task): ScheduledTaskQueuedSubscriberEntity {
    $taskSubscriber = new ScheduledTaskQueuedSubscriberEntity($task, $this->createSubscriber());
    $this->entityManager->persist($taskSubscriber);
    $this->entityManager->flush();
    return $taskSubscriber;
  }
}
