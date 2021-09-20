<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsBounceEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class StatisticsBounces {
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

  public function create(): StatisticsBounceEntity {
    $entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
    $queue = $this->newsletter->getLatestQueue();
    assert($queue instanceof SendingQueueEntity);
    $entity = new StatisticsBounceEntity(
      $this->newsletter,
      $queue,
      $this->subscriber
    );
    $entityManager->persist($entity);
    $entityManager->flush();
    return $entity;
  }
}
