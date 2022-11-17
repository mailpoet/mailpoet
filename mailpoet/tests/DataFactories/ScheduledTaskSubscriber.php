<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class ScheduledTaskSubscriber {
  /** @var EntityManager */
  private $entityManager;

  public function __construct() {
    $diContainer = ContainerWrapper::getInstance();
    $this->entityManager = $diContainer->get(EntityManager::class);
  }

  public function createUnprocessed(ScheduledTaskEntity $task, SubscriberEntity $subscriberEntity): ScheduledTaskSubscriberEntity {
    $taskSubscriber = new ScheduledTaskSubscriberEntity($task, $subscriberEntity);
    $this->entityManager->persist($taskSubscriber);
    $this->entityManager->flush();
    return $taskSubscriber;
  }

  public function createProcessed(ScheduledTaskEntity $task, SubscriberEntity $subscriberEntity): ScheduledTaskSubscriberEntity {
    $taskSubscriber = new ScheduledTaskSubscriberEntity($task, $subscriberEntity, 1);
    $this->entityManager->persist($taskSubscriber);
    $this->entityManager->flush();
    return $taskSubscriber;
  }

  public function createFailed(ScheduledTaskEntity $task, SubscriberEntity $subscriberEntity, string $error = null): ScheduledTaskSubscriberEntity {
    $taskSubscriber = new ScheduledTaskSubscriberEntity($task, $subscriberEntity, 1, 1, $error);
    $this->entityManager->persist($taskSubscriber);
    $this->entityManager->flush();
    return $taskSubscriber;
  }
}
