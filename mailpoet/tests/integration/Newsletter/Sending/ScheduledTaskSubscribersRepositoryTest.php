<?php declare(strict_types = 1);

namespace integration\Newsletter\Sending;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\ScheduledTaskSubscriber as TaskSubscriberFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Carbon\Carbon;

class ScheduledTaskSubscribersRepositoryTest extends \MailPoetTest {
  /** @var ScheduledTaskSubscribersRepository */
  private $repository;

  /** @var ScheduledTaskEntity */
  private $scheduledTask1;

  /** @var ScheduledTaskEntity */
  private $scheduledTask2;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(ScheduledTaskSubscribersRepository::class);
    $scheduledTaskFactory = new ScheduledTaskFactory();
    $subscriberFactory = new SubscriberFactory();
    $taskSubscriberFactory = new TaskSubscriberFactory();

    $subscriberUnprocessed = $subscriberFactory->withEmail('subscriberUnprocessed@email.com')->create();
    $subscriberProcessed = $subscriberFactory->withEmail('subscriberProcessed@email.com')->create();
    $subscriberFailed = $subscriberFactory->withEmail('subscriberFailed@email.com')->create();

    $this->scheduledTask1 = $scheduledTaskFactory->create('sending', ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->subDay());
    $this->scheduledTask2 = $scheduledTaskFactory->create('sending', ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->subDay());

    $taskSubscriberFactory->createUnprocessed($this->scheduledTask1, $subscriberUnprocessed);
    $taskSubscriberFactory->createProcessed($this->scheduledTask1, $subscriberProcessed);
    $taskSubscriberFactory->createFailed($this->scheduledTask1, $subscriberFailed, 'Error Message');
    $taskSubscriberFactory->createProcessed($this->scheduledTask2, $subscriberProcessed);
  }

  public function testCountProcessed() {
    $this->assertSame(2, $this->repository->countProcessed($this->scheduledTask1));
    $this->assertSame(1, $this->repository->countProcessed($this->scheduledTask2));
  }
}
