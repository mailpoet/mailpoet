<?php

namespace MailPoet\Entities;

use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="statistics_opens")
 */
class StatisticsOpenEntity {
  use AutoincrementedIdTrait;
  use CreatedAtTrait;

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
   * @ORM\Column(type="integer")
   * @var int|null
   */
  private $subscriberId;

}
