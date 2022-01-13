<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsWooCommercePurchaseEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class StatisticsWooCommercePurchases {
  protected $data;

  /** @var StatisticsClickEntity */
  private $click;

  /** @var SubscriberEntity|null  */
  private $subscriber;

  public function __construct(
    StatisticsClickEntity $click,
    $order
  ) {
    $this->data = [
      'order_id' => $order['id'],
      'order_currency' => $order['currency'],
      'order_price_total' => $order['total'],
    ];
    $this->subscriber = $click->getSubscriber();
    $this->click = $click;
  }

  public function create(): StatisticsWooCommercePurchaseEntity {
    $newsletter = $this->click->getNewsletter();
    assert($newsletter instanceof NewsletterEntity);
    $queue = $newsletter->getLatestQueue();
    assert($queue instanceof SendingQueueEntity);
    assert($this->subscriber instanceof SubscriberEntity);
    $entity = new StatisticsWooCommercePurchaseEntity(
      $newsletter,
      $queue,
      $this->click,
      $this->data['order_id'],
      $this->data['order_currency'],
      $this->data['order_price_total']
    );
    $entity->setSubscriber($this->subscriber);

    $entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
    $entityManager->persist($entity);
    $entityManager->flush();
    return $entity;
  }
}
