<?php

namespace MailPoet\Entities;

use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="newsletter_segment")
 */
class NewsletterSegmentEntity {
  use AutoincrementedIdTrait;
  use CreatedAtTrait;
  use UpdatedAtTrait;

  /**
   * @ORM\ManyToOne(targetEntity="MailPoet\Entities\NewsletterEntity", inversedBy="newsletter_segments")
   * @var NewsletterEntity
   */
  private $newsletter;

  /**
   * @ORM\ManyToOne(targetEntity="MailPoet\Entities\SegmentEntity")
   * @var SegmentEntity
   */
  private $segment;

  /**
   * @return NewsletterEntity
   */
  public function getNewsletter() {
    return $this->newsletter;
  }

  public function setNewsletter(NewsletterEntity $newsletter) {
    $this->newsletter = $newsletter;
  }

  /**
   * @return SegmentEntity
   */
  public function getSegment() {
    return $this->segment;
  }

  public function setSegment(SegmentEntity $segment) {
    $this->segment = $segment;
  }
}
