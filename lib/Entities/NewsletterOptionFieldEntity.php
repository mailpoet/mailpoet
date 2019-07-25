<?php

namespace MailPoet\Entities;

use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;

/**
 * @Entity()
 * @Table(name="newsletter_option_fields")
 */
class NewsletterOptionFieldEntity {
  use AutoincrementedIdTrait;
  use CreatedAtTrait;
  use UpdatedAtTrait;

  /**
   * @Column(type="string")
   * @var string
   */
  private $name;

  /**
   * @Column(type="string")
   * @var string
   */
  private $newsletter_type;

  /**
   * @return string
   */
  function getName() {
    return $this->name;
  }

  /**
   * @param string $name
   */
  function setName($name) {
    $this->name = $name;
  }

  /**
   * @return string
   */
  function getNewsletterType() {
    return $this->newsletter_type;
  }

  /**
   * @param string $newsletter_type
   */
  function setNewsletterType($newsletter_type) {
    $this->newsletter_type = $newsletter_type;
  }
}
