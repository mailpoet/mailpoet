<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class SendingQueue {
  /**
   * @var EntityManager
   */
  private $entityManager;

  public function __construct() {
    $diContainer = ContainerWrapper::getInstance();
    $this->entityManager = $diContainer->get(EntityManager::class);
  }

  public function create(ScheduledTaskEntity $task, ?NewsletterEntity $newsletter = null, \DateTimeInterface $deletedAt = null): SendingQueueEntity {
    $queue = new SendingQueueEntity();
    $queue->setTask($task);

    $newsletter = $newsletter ?: $this->entityManager->getReference(NewsletterEntity::class, rand(1, 9999));
    if ($newsletter) { // for phpstan because getReference can return null
      $queue->setNewsletter($newsletter);
    }

    if ($deletedAt) {
      $queue->setDeletedAt($deletedAt);
    }

    $this->entityManager->persist($queue);
    $this->entityManager->flush();

    return $queue;
  }
}
