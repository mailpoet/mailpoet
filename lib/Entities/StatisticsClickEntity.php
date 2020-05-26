<?php

namespace MailPoet\Entities;

use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\SafeToOneAssociationLoadTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="statistics_clicks")
 */
class StatisticsClickEntity {
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
   * @ORM\Column(type="integer")
   * @var int
   */
  private $subscriberId;

  /**
   * @ORM\ManyToOne(targetEntity="MailPoet\Entities\NewsletterLinkEntity", inversedBy="clicks")
   * @var NewsletterLinkEntity|null
   */
  private $link;

  /**
   * @ORM\Column(type="integer")
   * @var int
   */
  private $count;

  public function __construct(
    NewsletterEntity $newsletter,
    SendingQueueEntity $queue,
    int $subscriberId,
    NewsletterLinkEntity $link,
    int $count
  ) {
    $this->newsletter = $newsletter;
    $this->queue = $queue;
    $this->subscriberId = $subscriberId;
    $this->link = $link;
    $this->count = $count;
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
   * @return NewsletterLinkEntity|null
   */
  public function getLink() {
    $this->safelyLoadToOneAssociation('link');
    return $this->link;
  }

  /**
   * @param NewsletterEntity|null $newsletter
   */
  public function setNewsletter($newsletter) {
    $this->newsletter = $newsletter;
  }

  /**
   * @param SendingQueueEntity|null $queue
   */
  public function setQueue($queue) {
    $this->queue = $queue;
  }

  /**
   * @param int $subscriberId
   */
  public function setSubscriberId($subscriberId) {
    $this->subscriberId = $subscriberId;
  }

  /**
   * @param NewsletterLinkEntity|null $link
   */
  public function setLink($link) {
    $this->link = $link;
  }

  /**
   * @param int $count
   */
  public function setCount(int $count) {
    $this->count = $count;
  }
}
