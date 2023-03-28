<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Listing\Handler;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\ScheduledTaskSubscriber as TaskSubscriberFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Carbon\Carbon;

class ScheduledTaskSubscribersListingRepositoryTest extends \MailPoetTest {
  /** @var Handler */
  protected $listingHandler;

  /** @var ScheduledTaskSubscribersListingRepository */
  private $repository;

  /** @var ScheduledTaskFactory */
  private $scheduledTaskFactory;

  /** @var SubscriberFactory */
  private $subscriberFactory;

  /** @var TaskSubscriberFactory */
  private $taskSubscriberFactory;

  /** @var ScheduledTaskEntity */
  private $scheduledTask;

  public function _before() {
    parent::_before();
    $this->listingHandler = $this->diContainer->get(Handler::class);
    $this->repository = $this->diContainer->get(ScheduledTaskSubscribersListingRepository::class);
    $this->scheduledTaskFactory = new ScheduledTaskFactory();
    $this->subscriberFactory = new SubscriberFactory();
    $this->taskSubscriberFactory = new TaskSubscriberFactory();

    // Subscribers
    $subscriberUnprocessed = $this->subscriberFactory->withEmail('subscriberUprocessed@email.com')->create();
    $subscriberProcessed = $this->subscriberFactory->withEmail('subscriberProcessed@email.com')->create();
    $subscriberFailed = $this->subscriberFactory->withEmail('subscriberFailed@email.com')->create();
    $this->subscriberFactory->withEmail('subscriberNotIncluded@email.com')->create();

    // Scheduled Task
    $this->scheduledTask = $this->scheduledTaskFactory->create('sending', ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->subDay());

    // Task Subscribers
    $this->taskSubscriberFactory->createUnprocessed($this->scheduledTask, $subscriberUnprocessed);
    $this->taskSubscriberFactory->createProcessed($this->scheduledTask, $subscriberProcessed);
    $this->taskSubscriberFactory->createFailed($this->scheduledTask, $subscriberFailed, 'Error Message');
  }

  public function testItGenerateCorrectGroups() {
    $listingData = [
      'group' => 'all',
      'params' => [ 'task_ids' => [$this->scheduledTask->getId()]],
    ];
    [$all, $sent, $failed, $unprocessed] = $this->repository->getGroups($this->listingHandler->getListingDefinition($listingData));
    expect($all['name'])->equals('all');
    expect($all['label'])->equals('All');
    expect($all['count'])->equals(3);

    expect($sent['name'])->equals('sent');
    expect($sent['label'])->equals('Sent');
    expect($sent['count'])->equals(1);

    expect($failed['name'])->equals('failed');
    expect($failed['label'])->equals('Failed');
    expect($failed['count'])->equals(1);

    expect($unprocessed['name'])->equals('unprocessed');
    expect($unprocessed['label'])->equals('Unprocessed');
    expect($unprocessed['count'])->equals(1);
  }

  public function testItReturnCorrectDataAndCountForGroupAll() {
    $listingData = [
      'group' => 'all',
      'params' => [ 'task_ids' => [$this->scheduledTask->getId()]],
    ];
    $tasksSubscribers = $this->repository->getData($this->listingHandler->getListingDefinition($listingData));
    $count = $this->repository->getCount($this->listingHandler->getListingDefinition($listingData));
    expect($tasksSubscribers)->count(3);
    expect($count)->equals(3);

    $this->assertInstanceOf(ScheduledTaskSubscriberEntity::class, $tasksSubscribers[0]);
    $this->assertInstanceOf(SubscriberEntity::class, $tasksSubscribers[0]->getSubscriber());
    expect($tasksSubscribers[0]->getSubscriber()->getEmail())->equals('subscriberUprocessed@email.com');

    $this->assertInstanceOf(ScheduledTaskSubscriberEntity::class, $tasksSubscribers[1]);
    $this->assertInstanceOf(SubscriberEntity::class, $tasksSubscribers[1]->getSubscriber());
    expect($tasksSubscribers[1]->getSubscriber()->getEmail())->equals('subscriberProcessed@email.com');

    $this->assertInstanceOf(ScheduledTaskSubscriberEntity::class, $tasksSubscribers[2]);
    $this->assertInstanceOf(SubscriberEntity::class, $tasksSubscribers[2]->getSubscriber());
    expect($tasksSubscribers[2]->getSubscriber()->getEmail())->equals('subscriberFailed@email.com');
  }

  public function testItCanFilterByGroup() {
    $listingData = [
      'group' => 'failed',
      'params' => [ 'task_ids' => [$this->scheduledTask->getId()]],
    ];
    $tasksSubscribers = $this->repository->getData($this->listingHandler->getListingDefinition($listingData));
    $count = $this->repository->getCount($this->listingHandler->getListingDefinition($listingData));
    expect($tasksSubscribers)->count(1);
    expect($count)->equals(1);

    $this->assertInstanceOf(ScheduledTaskSubscriberEntity::class, $tasksSubscribers[0]);
    $this->assertInstanceOf(SubscriberEntity::class, $tasksSubscribers[0]->getSubscriber());
    expect($tasksSubscribers[0]->getSubscriber()->getEmail())->equals('subscriberFailed@email.com');
  }

  public function testItCanSearchByEmail() {
    $listingData = [
      'group' => 'all',
      'params' => [ 'task_ids' => [$this->scheduledTask->getId()]],
      'search' => 'subscriberProcessed@',
    ];
    $tasksSubscribers = $this->repository->getData($this->listingHandler->getListingDefinition($listingData));
    $count = $this->repository->getCount($this->listingHandler->getListingDefinition($listingData));
    expect($tasksSubscribers)->count(1);
    expect($count)->equals(1);

    $this->assertInstanceOf(ScheduledTaskSubscriberEntity::class, $tasksSubscribers[0]);
    $this->assertInstanceOf(SubscriberEntity::class, $tasksSubscribers[0]->getSubscriber());
    expect($tasksSubscribers[0]->getSubscriber()->getEmail())->equals('subscriberProcessed@email.com');
  }
}
