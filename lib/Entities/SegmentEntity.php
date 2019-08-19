<?php

namespace MailPoet\Entities;

use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\DeletedAtTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;

/**
 * @Entity()
 * @Table(name="segments")
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
   * @Column(type="string")
   * @var string
   */
  private $name;

  /**
   * @Column(type="string")
   * @var string
   */
  private $type;

  /**
   * @Column(type="string")
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
