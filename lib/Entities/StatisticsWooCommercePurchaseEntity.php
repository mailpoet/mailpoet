<?php

namespace MailPoet\Entities;

use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
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
   * @ORM\ManyToOne(targetEntity="MailPoet\Entities\StatisticsClickEntity")
   * @ORM\JoinColumn(name="click_id", referencedColumnName="id")
   * @var StatisticsClickEntity|null
   */
  private $click;

  /**
   * @ORM\Column(type="integer")
   * @var int|null
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
}
