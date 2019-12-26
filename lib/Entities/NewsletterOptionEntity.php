<?php

namespace MailPoet\Entities;

use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="newsletter_option")
 */
class NewsletterOptionEntity {
  use AutoincrementedIdTrait;
  use CreatedAtTrait;
  use UpdatedAtTrait;

  /**
   * @ORM\Column(type="text")
   * @var string|null
   */
  private $value;

  /**
   * @ORM\ManyToOne(targetEntity="MailPoet\Entities\NewsletterEntity", inversedBy="options")
   * @var NewsletterEntity
   */
  private $newsletter;

  /**
   * @ORM\ManyToOne(targetEntity="MailPoet\Entities\NewsletterOptionFieldEntity", inversedBy="options")
   * @var NewsletterOptionFieldEntity
   */
  private $option_field;

  /**
   * @return string|null
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * @param string|null $value
   */
  public function setValue($value) {
    $this->value = $value;
  }

  /**
   * @return NewsletterEntity
   */
  public function getNewsletter() {
    return $this->newsletter;
  }

  /**
   * @param NewsletterEntity $newsletter
   */
  public function setNewsletter($newsletter) {
    $this->newsletter = $newsletter;
  }

  /**
   * @return NewsletterOptionFieldEntity
   */
  public function getOptionField() {
    return $this->option_field;
  }

  /**
   * @param NewsletterOptionFieldEntity $option_field
   */
  public function setOptionField($option_field) {
    $this->option_field = $option_field;
  }
}
