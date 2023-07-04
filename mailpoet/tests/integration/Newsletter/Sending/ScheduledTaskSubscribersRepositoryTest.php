<?php declare(strict_types = 1);

namespace integration\Newsletter\Sending;

use MailPoet\Entities\ScheduledTaskEntity;
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

    $taskSubscriberFactory->createUnprocessed($this->scheduledTask1, $this->subscriberUnprocessed);
    $taskSubscriberFactory->createProcessed($this->scheduledTask1, $this->subscriberProcessed);
    $taskSubscriberFactory->createFailed($this->scheduledTask1, $subscriberFailed, 'Error Message');

    $taskSubscriberFactory->createUnprocessed($this->scheduledTask2, $this->subscriberUnprocessed);
    $taskSubscriberFactory->createProcessed($this->scheduledTask2, $this->subscriberProcessed);
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
}
