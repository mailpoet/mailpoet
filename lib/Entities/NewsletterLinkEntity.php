<?php

namespace MailPoet\Entities;

use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;
use MailPoetVendor\Doctrine\Common\Collections\ArrayCollection;
use MailPoetVendor\Doctrine\Common\Collections\Criteria;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="newsletter_links")
 */
class NewsletterLinkEntity {
  use AutoincrementedIdTrait;
  use CreatedAtTrait;
  use UpdatedAtTrait;

  /**
   * @ORM\Column(type="integer")
   * @var integer
   */
  private $newsletter_id;

  /**
   * @ORM\OneToOne(targetEntity="MailPoet\Entities\NewsletterEntity")
   * @ORM\JoinColumn(name="newsletter_id", referencedColumnName="id")
   * @var NewsletterEntity|null
   */
  private $newsletter;

  /**
   * @ORM\OneToOne(targetEntity="MailPoet\Entities\SendingQueueEntity")
   * @ORM\JoinColumn(name="queue_id", referencedColumnName="id")
   * @var SendingQueueEntity|null
   */
  private $queue;

  /**
   * @ORM\Column(type="string")
   * @var string|null
   */
  private $url;

  /**
   * @ORM\Column(type="string")
   * @var string|null
   */
  private $hash;

  /**
   * Extra lazy is here for `getTotalClicksCount`.
   * If we didn't specify extra lazy the function would load all clicks and count them. This way it uses a single count query.
   * @ORM\OneToMany(targetEntity="MailPoet\Entities\StatisticsClicksEntity", mappedBy="link", fetch="EXTRA_LAZY")
   * @var StatisticsClicksEntity[]|ArrayCollection
   */
  private $clicks;

  /**
   * @return NewsletterEntity|null
   */
  public function getNewsletter() {
    return $this->newsletter;
  }

  /**
   * @return SendingQueueEntity|null
   */
  public function getQueue() {
    return $this->queue;
  }

  /**
   * @return string|null
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * @return string|null
   */
  public function getHash() {
    return $this->hash;
  }

  /**
   * @return int
   */
  function getTotalClicksCount() {
    $criteria = Criteria::create()
      ->where(Criteria::expr()->eq("link", $this));
    return $this->clicks->matching($criteria)->count();
  }
}
