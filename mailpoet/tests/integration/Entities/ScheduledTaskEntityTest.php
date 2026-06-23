<?php declare(strict_types = 1);

namespace MailPoet\Entities;

use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\ScheduledTaskQueuedSubscriber as ScheduledTaskQueuedSubscriberFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Carbon\Carbon;

class ScheduledTaskEntityTest extends \MailPoetTest {
  public function testItCountsQueuedSubscribers(): void {
    $task = $this->createTask();
    verify($task->getQueuedCount())->equals(0);

    $this->queueSubscriber($task);
    $this->queueSubscriber($task);

    // reload so the EXTRA_LAZY collection counts via the database, not in-memory state
    $task = $this->reloadTask($task);
    verify($task->getQueuedCount())->equals(2);
  }

  public function testItReturnsNullFirstQueuedSubscriberWhenQueueEmpty(): void {
    $task = $this->createTask();
    $this->assertNull($task->getFirstQueuedSubscriber());
  }

  public function testItReturnsTheLowestIdFirstQueuedSubscriber(): void {
    $task = $this->createTask();
    $first = $this->queueSubscriber($task);
    $second = $this->queueSubscriber($task);

    $task = $this->reloadTask($task);

    $firstQueued = $task->getFirstQueuedSubscriber();
    $this->assertInstanceOf(SubscriberEntity::class, $firstQueued);
    $lowestId = min((int)$first->getId(), (int)$second->getId());
    verify((int)$firstQueued->getId())->equals($lowestId);
  }

  private function createTask(): ScheduledTaskEntity {
    return (new ScheduledTaskFactory())->create('sending', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now());
  }

  private function queueSubscriber(ScheduledTaskEntity $task): SubscriberEntity {
    $subscriber = (new SubscriberFactory())->create();
    (new ScheduledTaskQueuedSubscriberFactory())->create($task, $subscriber);
    return $subscriber;
  }

  private function reloadTask(ScheduledTaskEntity $task): ScheduledTaskEntity {
    $this->entityManager->clear();
    $reloaded = $this->entityManager->find(ScheduledTaskEntity::class, (int)$task->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $reloaded);
    return $reloaded;
  }
}
