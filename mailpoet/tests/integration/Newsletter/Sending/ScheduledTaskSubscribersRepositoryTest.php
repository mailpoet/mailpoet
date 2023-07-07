<?php declare(strict_types = 1);

namespace integration\Newsletter\Sending;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\ScheduledTaskSubscriber as TaskSubscriberFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Carbon\Carbon;

class ScheduledTaskSubscribersRepositoryTest extends \MailPoetTest {
  /** @var ScheduledTaskSubscribersRepository */
  private $repository;

  /** @var SubscriberFactory */
  private $subscriberFactory;

  /** @var ScheduledTaskEntity */
  private $scheduledTask1;

  /** @var ScheduledTaskEntity */
  private $scheduledTask2;

  /** @var SubscriberEntity */
  private $subscriberUnprocessed;

  /** @var SubscriberEntity */
  private $subscriberProcessed;

  /** @var ScheduledTaskSubscriberEntity */
  private $taskSubscriber1;

  /** @var ScheduledTaskSubscriberEntity */
  private $taskSubscriber2;

  /** @var ScheduledTaskSubscriberEntity */
  private $taskSubscriber3;

  /** @var ScheduledTaskSubscriberEntity */
  private $taskSubscriber4;

  /** @var ScheduledTaskSubscriberEntity */
  private $taskSubscriber5;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(ScheduledTaskSubscribersRepository::class);
    $scheduledTaskFactory = new ScheduledTaskFactory();
    $this->subscriberFactory = new SubscriberFactory();
    $taskSubscriberFactory = new TaskSubscriberFactory();

    $this->subscriberUnprocessed = $this->subscriberFactory->withEmail('subscriberUnprocessed@email.com')->create();
    $this->subscriberProcessed = $this->subscriberFactory->withEmail('subscriberProcessed@email.com')->create();
    $subscriberFailed = $this->subscriberFactory->withEmail('subscriberFailed@email.com')->create();
    $this->subscriberFactory->withEmail('subscriberNotIncluded@email.com')->create();

    $this->scheduledTask1 = $scheduledTaskFactory->create('sending', ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->subDay());
    $this->scheduledTask2 = $scheduledTaskFactory->create('sending', ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->subDay());

    $this->taskSubscriber1 = $taskSubscriberFactory->createUnprocessed($this->scheduledTask1, $this->subscriberUnprocessed);
    $this->taskSubscriber2 = $taskSubscriberFactory->createProcessed($this->scheduledTask1, $this->subscriberProcessed);
    $this->taskSubscriber3 = $taskSubscriberFactory->createFailed($this->scheduledTask1, $subscriberFailed, 'Error Message');

    $this->taskSubscriber4 = $taskSubscriberFactory->createUnprocessed($this->scheduledTask2, $this->subscriberUnprocessed);
    $this->taskSubscriber5 = $taskSubscriberFactory->createProcessed($this->scheduledTask2, $this->subscriberProcessed);
  }

  public function testItSetsSubscribers() {
    $subscriber = $this->subscriberFactory->withEmail('newsubscriber@email.com')->create();

    $this->assertCount(3, $this->repository->findBy(['task' => $this->scheduledTask1]));
    $this->repository->setSubscribers($this->scheduledTask1, [$subscriber->getId()]);
    $task1Subscribers = $this->repository->findBy(['task' => $this->scheduledTask1]);
    $this->assertCount(1, $task1Subscribers);
    $this->assertInstanceOf(SubscriberEntity::class, $task1Subscribers[0]->getSubscriber());
    $this->assertEquals($subscriber->getId(), $task1Subscribers[0]->getSubscriber()->getId());

    // check that setSubscribers() does not delete subscribers from other tasks
    $task2Subscribers = $this->repository->findBy(['task' => $this->scheduledTask2]);
    $this->assertCount(2, $task2Subscribers);
    $this->assertInstanceOf(SubscriberEntity::class, $task2Subscribers[0]->getSubscriber());
    $this->assertInstanceOf(SubscriberEntity::class, $task2Subscribers[1]->getSubscriber());
    $this->assertEquals($this->subscriberUnprocessed->getId(), $task2Subscribers[0]->getSubscriber()->getId());
    $this->assertEquals($this->subscriberProcessed->getId(), $task2Subscribers[1]->getSubscriber()->getId());
  }

  public function testItDeleteByScheduledTaskAndSubscriberIds() {
    $this->repository->deleteByScheduledTaskAndSubscriberIds($this->scheduledTask1, [$this->taskSubscriber1->getSubscriberId()]);
    $this->assertSame([$this->taskSubscriber2, $this->taskSubscriber3], $this->repository->findBy(['task' => $this->scheduledTask1]));
    $this->assertSame([$this->taskSubscriber4, $this->taskSubscriber5], $this->repository->findBy(['task' => $this->scheduledTask2]));

    $this->repository->deleteByScheduledTaskAndSubscriberIds($this->scheduledTask2, [$this->taskSubscriber4->getSubscriberId(), $this->taskSubscriber5->getSubscriberId()]);
    $this->assertSame([$this->taskSubscriber2, $this->taskSubscriber3], $this->repository->findBy(['task' => $this->scheduledTask1]));
    $this->assertSame([], $this->repository->findBy(['task' => $this->scheduledTask2]));
  }

  public function testCountProcessed() {
    $this->assertSame(2, $this->repository->countProcessed($this->scheduledTask1));
    $this->assertSame(1, $this->repository->countProcessed($this->scheduledTask2));

    $subscriberId = $this->subscriberUnprocessed->getId();
    $this->assertIsInt($subscriberId);
    $this->repository->updateProcessedSubscribers($this->scheduledTask2, [$subscriberId]);
    $this->assertSame(2, $this->repository->countProcessed($this->scheduledTask2));
  }

  public function testCountUnprocessed() {
    $this->assertSame(1, $this->repository->countUnprocessed($this->scheduledTask1));
    $this->assertSame(1, $this->repository->countUnprocessed($this->scheduledTask2));

    $subscriberId = $this->subscriberUnprocessed->getId();
    $this->assertIsInt($subscriberId);
    $this->repository->updateProcessedSubscribers($this->scheduledTask2, [$subscriberId]);
    $this->assertSame(0, $this->repository->countUnprocessed($this->scheduledTask2));
  }
}
