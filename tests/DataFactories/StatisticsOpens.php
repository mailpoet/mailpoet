<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class StatisticsOpens {
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

  public function create(): StatisticsOpenEntity {
    $entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
    $queue = $this->newsletter->getLatestQueue();
    assert($queue instanceof SendingQueueEntity);
    $entity = new StatisticsOpenEntity(
      $this->newsletter,
      $queue,
      $this->subscriber
    );
    $entityManager->persist($entity);
    $entityManager->flush();
    return $entity;
  }
}
