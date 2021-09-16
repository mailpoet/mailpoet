<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class StatisticsClicks {
  protected $data;

  /** @var NewsletterLinkEntity */
  private $newsletterLink;

  /** @var SubscriberEntity */
  private $subscriber;

  public function __construct(
      NewsletterLinkEntity $newsletterLink,
      SubscriberEntity $subscriber
  ) {
    $this->data = [
      'count' => 1,
    ];
    $this->newsletterLink = $newsletterLink;
    $this->subscriber = $subscriber;
  }

  public function withCount($count) {
    $this->data['count'] = $count;
    return $this;
  }

  public function create(): StatisticsClickEntity {
    $entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
    $newsletter = $this->newsletterLink->getNewsletter();
    assert($newsletter instanceof NewsletterEntity);
    $queue = $newsletter->getLatestQueue();
    assert($queue instanceof SendingQueueEntity);
    $entity = new StatisticsClickEntity(
      $newsletter,
      $queue,
      $this->subscriber,
      $this->newsletterLink,
      $this->data['count']
    );
    $entityManager->persist($entity);
    $entityManager->flush();
    return $entity;
  }
}
