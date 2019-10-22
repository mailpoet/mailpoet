<?php

namespace MailPoet\Entities;

use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\DeletedAtTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;
use MailPoetVendor\Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(name="segments")
 */
class SegmentEntity {
  use AutoincrementedIdTrait;
  use CreatedAtTrait;
  use UpdatedAtTrait;
  use DeletedAtTrait;

  const TYPE_WP_USERS = 'wp_users';
  const TYPE_WC_USERS = 'woocommerce_users';
  const TYPE_DEFAULT = 'default';

  /**
   * @ORM\Column(type="string")
   * @Assert\NotBlank()
   * @var string
   */
  private $name;

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $type;

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $description;

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
  function getType() {
    return $this->type;
  }

  /**
   * @param string $type
   */
  function setType($type) {
    $this->type = $type;
  }

  /**
   * @return string
   */
  function getDescription() {
    return $this->description;
  }

  /**
   * @param string $description
   */
  function setDescription($description) {
    $this->description = $description;
  }
}
