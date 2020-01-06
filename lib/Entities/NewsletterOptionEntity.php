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
  private $optionField;

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
    return $this->optionField;
  }

  /**
   * @param NewsletterOptionFieldEntity $optionField
   */
  public function setOptionField($optionField) {
    $this->optionField = $optionField;
  }
}
