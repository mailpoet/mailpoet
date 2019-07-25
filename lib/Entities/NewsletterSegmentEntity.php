<?php

namespace MailPoet\Entities;

use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;

/**
 * @Entity()
 * @Table(name="newsletter_segment")
 */
class NewsletterSegmentEntity {
  use AutoincrementedIdTrait;
  use CreatedAtTrait;
  use UpdatedAtTrait;

  /**
   * @ManyToOne(targetEntity="MailPoet\Entities\NewsletterEntity", inversedBy="newsletter_segments")
   * @var NewsletterEntity
   */
  private $newsletter;

  /**
   * @ManyToOne(targetEntity="MailPoet\Entities\SegmentEntity")
   * @var SegmentEntity
   */
  private $segment;

  /**
   * @return NewsletterEntity
   */
  function getNewsletter() {
    return $this->newsletter;
  }

  function setNewsletter(NewsletterEntity $newsletter) {
    $this->newsletter = $newsletter;
  }

  /**
   * @return SegmentEntity
   */
  function getSegment() {
    return $this->segment;
  }

  function setSegment(SegmentEntity $segment) {
    $this->segment = $segment;
  }
}
