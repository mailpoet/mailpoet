<?php

namespace MailPoet\Entities;

use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;
use MailPoetVendor\Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(name="newsletter_option_fields")
 */
class NewsletterOptionFieldEntity {
  use AutoincrementedIdTrait;
  use CreatedAtTrait;
  use UpdatedAtTrait;

  /**
   * @ORM\Column(type="string")
   * @Assert\NotBlank()
   * @var string
   */
  private $name;

  /**
   * @ORM\Column(type="string")
   * @Assert\NotBlank()
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
