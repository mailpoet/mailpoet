<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Models\Subscriber;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class StatisticsOpens {
  protected $data;

  public function __construct(Newsletter $newsletter, Subscriber $subscriber) {
    $this->data = [
      'newsletter_id' => $newsletter->id,
      'subscriber_id' => $subscriber->id,
      'queue_id' => $newsletter->getQueue()->id,
    ];
  }

  public function withCount($count) {
    $this->data['count'] = $count;
    return $this;
  }

  /** @return StatisticsOpenEntity */
  public function create() {
    $entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
    $entity = new StatisticsOpenEntity(
      $entityManager->getReference(NewsletterEntity::class, $this->data['newsletter_id']),
      $entityManager->getReference(SendingQueueEntity::class, $this->data['queue_id']),
      $entityManager->getReference(SubscriberEntity::class, $this->data['subscriber_id'])
    );
    $entityManager->persist($entity);
    $entityManager->flush();
    return $entity;
  }
}
