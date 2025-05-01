<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Assert;

class StatisticsUnsubscribes {
  protected $data;

  /** @var NewsletterEntity */
  private $newsletter;

  /** @var SubscriberEntity */
  private $subscriber;

  public function __construct(
    NewsletterEntity $newsletter,
    SubscriberEntity $subscriber
  ) {
    $this->newsletter = $newsletter;
    $this->subscriber = $subscriber;
  }

  public function withCreatedAt(\DateTimeInterface $createdAt): self {
    $this->data['createdAt'] = $createdAt;
    return $this;
  }

  public function create(): StatisticsUnsubscribeEntity {
    $entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
    $queue = $this->newsletter->getLatestQueue();
    Assert::assertInstanceOf(SendingQueueEntity::class, $queue);
    $entity = new StatisticsUnsubscribeEntity(
      $this->newsletter,
      $queue,
      $this->subscriber
    );
    if (($this->data['createdAt'] ?? null) instanceof \DateTimeInterface) {
      $entity->setCreatedAt($this->data['createdAt']);
    }
    $entityManager->persist($entity);
    $entityManager->flush();
    return $entity;
  }
}
