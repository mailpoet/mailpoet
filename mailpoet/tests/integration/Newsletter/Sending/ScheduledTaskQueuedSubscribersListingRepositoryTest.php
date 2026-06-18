<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskQueuedSubscriberEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Listing\Handler;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\ScheduledTaskQueuedSubscriber as QueuedSubscriberFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

class ScheduledTaskQueuedSubscribersListingRepositoryTest extends \MailPoetTest {
  /** @var Handler */
  private $listingHandler;

  /** @var ScheduledTaskQueuedSubscribersListingRepository */
  private $repository;

  /** @var ScheduledTaskFactory */
  private $scheduledTaskFactory;

  /** @var SubscriberFactory */
  private $subscriberFactory;

  /** @var QueuedSubscriberFactory */
  private $queuedSubscriberFactory;

  /** @var ScheduledTaskEntity */
  private $scheduledTask;

  public function _before() {
    parent::_before();
    $this->listingHandler = $this->diContainer->get(Handler::class);
    $this->repository = $this->diContainer->get(ScheduledTaskQueuedSubscribersListingRepository::class);
    $this->scheduledTaskFactory = new ScheduledTaskFactory();
    $this->subscriberFactory = new SubscriberFactory();
    $this->queuedSubscriberFactory = new QueuedSubscriberFactory();

    $this->scheduledTask = $this->scheduledTaskFactory->create('sending', ScheduledTaskEntity::STATUS_SCHEDULED);

    // Three pending recipients queued for this task, plus one queued for a
    // different task and one not queued at all (must be excluded).
    $this->queuedSubscriberFactory->create($this->scheduledTask, $this->subscriberFactory->withEmail('charlie@queue.com')->withFirstName('Charlie')->create());
    $this->queuedSubscriberFactory->create($this->scheduledTask, $this->subscriberFactory->withEmail('alice@queue.com')->withFirstName('Alice')->create());
    $this->queuedSubscriberFactory->create($this->scheduledTask, $this->subscriberFactory->withEmail('bob@queue.com')->withFirstName('Bob')->create());

    $otherTask = $this->scheduledTaskFactory->create('sending', ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->queuedSubscriberFactory->create($otherTask, $this->subscriberFactory->withEmail('other@queue.com')->create());

    $this->subscriberFactory->withEmail('notqueued@queue.com')->create();
  }

  public function testItReturnsQueuedSubscribersScopedToTheTask() {
    $definition = $this->listingHandler->getListingDefinition([
      'params' => ['task_ids' => [$this->scheduledTask->getId()]],
    ]);
    $queuedSubscribers = $this->repository->getData($definition);
    $count = $this->repository->getCount($definition);

    verify($queuedSubscribers)->arrayCount(3);
    verify($count)->equals(3);
    foreach ($queuedSubscribers as $queuedSubscriber) {
      $this->assertInstanceOf(ScheduledTaskQueuedSubscriberEntity::class, $queuedSubscriber);
      $this->assertInstanceOf(SubscriberEntity::class, $queuedSubscriber->getSubscriber());
    }
    $emails = array_map(function (ScheduledTaskQueuedSubscriberEntity $queuedSubscriber): ?string {
      $subscriber = $queuedSubscriber->getSubscriber();
      return $subscriber ? $subscriber->getEmail() : null;
    }, $queuedSubscribers);
    verify($emails)->arrayContains('alice@queue.com');
    verify($emails)->arrayContains('bob@queue.com');
    verify($emails)->arrayContains('charlie@queue.com');
    verify($emails)->arrayNotContains('other@queue.com');
    verify($emails)->arrayNotContains('notqueued@queue.com');
  }

  public function testItReturnsUnprocessedGroupCount() {
    $definition = $this->listingHandler->getListingDefinition([
      'params' => ['task_ids' => [$this->scheduledTask->getId()]],
    ]);
    $groups = $this->repository->getGroups($definition);
    verify($groups)->arrayCount(1);
    verify($groups[0]['name'])->equals('unprocessed');
    verify($groups[0]['label'])->equals('Unprocessed');
    verify($groups[0]['count'])->equals(3);
  }

  public function testItCanSearchByEmail() {
    $definition = $this->listingHandler->getListingDefinition([
      'params' => ['task_ids' => [$this->scheduledTask->getId()]],
      'search' => 'alice@',
    ]);
    $queuedSubscribers = $this->repository->getData($definition);
    $count = $this->repository->getCount($definition);

    verify($queuedSubscribers)->arrayCount(1);
    verify($count)->equals(1);
    $subscriber = $queuedSubscribers[0]->getSubscriber();
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    verify($subscriber->getEmail())->equals('alice@queue.com');
  }

  public function testItSortsBySubscriberEmail() {
    $definition = $this->listingHandler->getListingDefinition([
      'params' => ['task_ids' => [$this->scheduledTask->getId()]],
      'sort_by' => 'subscriberId',
      'sort_order' => 'asc',
    ]);
    $queuedSubscribers = $this->repository->getData($definition);
    $emails = array_map(function (ScheduledTaskQueuedSubscriberEntity $queuedSubscriber): ?string {
      $subscriber = $queuedSubscriber->getSubscriber();
      return $subscriber ? $subscriber->getEmail() : null;
    }, $queuedSubscribers);
    verify($emails)->equals(['alice@queue.com', 'bob@queue.com', 'charlie@queue.com']);
  }

  public function testItPaginatesQueuedSubscribers() {
    $pageParams = [
      'params' => ['task_ids' => [$this->scheduledTask->getId()]],
      'sort_by' => 'subscriberId',
      'sort_order' => 'asc',
      'limit' => 2,
    ];
    $firstPage = $this->repository->getData($this->listingHandler->getListingDefinition($pageParams + ['offset' => 0]));
    $secondPage = $this->repository->getData($this->listingHandler->getListingDefinition($pageParams + ['offset' => 2]));

    verify($firstPage)->arrayCount(2);
    verify($secondPage)->arrayCount(1);
    // The total count is independent of the page window.
    verify($this->repository->getCount($this->listingHandler->getListingDefinition($pageParams + ['offset' => 0])))->equals(3);
  }

  public function testItIsEmptyForACompletedSend() {
    $completedTask = $this->scheduledTaskFactory->create('sending', ScheduledTaskEntity::STATUS_COMPLETED);
    $definition = $this->listingHandler->getListingDefinition([
      'params' => ['task_ids' => [$completedTask->getId()]],
    ]);
    verify($this->repository->getData($definition))->arrayCount(0);
    verify($this->repository->getCount($definition))->equals(0);
    $groups = $this->repository->getGroups($definition);
    verify($groups[0]['count'])->equals(0);
  }
}
