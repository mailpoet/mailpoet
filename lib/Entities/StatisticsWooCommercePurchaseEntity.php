<?php

namespace MailPoet\Entities;

use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\SafeToOneAssociationLoadTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="statistics_woocommerce_purchases")
 */
class StatisticsWooCommercePurchaseEntity {
  use AutoincrementedIdTrait;
  use CreatedAtTrait;
  use UpdatedAtTrait;
  use SafeToOneAssociationLoadTrait;

  /**
   * @ORM\ManyToOne(targetEntity="MailPoet\Entities\NewsletterEntity")
   * @ORM\JoinColumn(name="newsletter_id", referencedColumnName="id")
   * @var NewsletterEntity|null
   */
  private $newsletter;

  /**
   * @ORM\ManyToOne(targetEntity="MailPoet\Entities\SendingQueueEntity")
   * @ORM\JoinColumn(name="queue_id", referencedColumnName="id")
   * @var SendingQueueEntity|null
   */
  private $queue;

  /**
   * @ORM\ManyToOne(targetEntity="MailPoet\Entities\SubscriberEntity")
   * @ORM\JoinColumn(name="subscriber_id", referencedColumnName="id")
   * @var SubscriberEntity|null
   */
  private $subscriber;

  /**
   * @ORM\ManyToOne(targetEntity="MailPoet\Entities\StatisticsClickEntity", inversedBy="wooCommercePurchases")
   * @ORM\JoinColumn(name="click_id", referencedColumnName="id")
   * @var StatisticsClickEntity|null
   */
  private $click;

  /**
   * @ORM\Column(type="integer")
   * @var int
   */
  private $orderId;

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $orderCurrency;

  /**
   * @ORM\Column(type="float")
   * @var float
   */
  private $orderPriceTotal;

  public function __construct(NewsletterEntity $newsletter, SendingQueueEntity $queue, StatisticsClickEntity $click, int $orderId, string $orderCurrency, float $orderPriceTotal ) {
    $this->newsletter = $newsletter;
    $this->queue = $queue;
    $this->click = $click;
    $this->orderId = $orderId;
    $this->orderCurrency = $orderCurrency;
    $this->orderPriceTotal = $orderPriceTotal;
  }

  /**
   * @return NewsletterEntity|null
   */
  public function getNewsletter() {
    $this->safelyLoadToOneAssociation('newsletter');
    return $this->newsletter;
  }

  /**
   * @return SendingQueueEntity|null
   */
  public function getQueue() {
    $this->safelyLoadToOneAssociation('queue');
    return $this->queue;
  }

  /**
   * @return SubscriberEntity|null
   */
  public function getSubscriber() {
    $this->safelyLoadToOneAssociation('subscriber');
    return $this->subscriber;
  }

  /**
   * @return StatisticsClickEntity|null
   */
  public function getClick() {
    $this->safelyLoadToOneAssociation('click');
    return $this->click;
  }

  /**
   * @return int
   */
  public function getOrderId(): int {
    return $this->orderId;
  }

  /**
   * @param SubscriberEntity|null $subscriber
   */
  public function setSubscriber($subscriber) {
    $this->subscriber = $subscriber;
  }

  /**
   * @return string
   */
  public function getOrderCurrency(): string {
    return $this->orderCurrency;
  }

  /**
   * @return float
   */
  public function getOrderPriceTotal(): float {
    return $this->orderPriceTotal;
  }
}
