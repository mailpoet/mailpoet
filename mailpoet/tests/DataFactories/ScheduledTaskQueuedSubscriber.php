<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskQueuedSubscriberEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class ScheduledTaskQueuedSubscriber {
  /** @var EntityManager */
  private $entityManager;

  public function __construct() {
    $diContainer = ContainerWrapper::getInstance();
    $this->entityManager = $diContainer->get(EntityManager::class);
  }

  public function create(ScheduledTaskEntity $task, SubscriberEntity $subscriber): ScheduledTaskQueuedSubscriberEntity {
    $queuedSubscriber = new ScheduledTaskQueuedSubscriberEntity($task, $subscriber);
    $this->entityManager->persist($queuedSubscriber);
    $this->entityManager->flush();
    return $queuedSubscriber;
  }
}
