<?php

namespace MailPoet\Entities;

use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;

/**
 * @Entity()
 * @Table(name="user_flags")
 */
class UserFlagEntity {
  use AutoincrementedIdTrait;
  use CreatedAtTrait;
  use UpdatedAtTrait;

  /**
   * @Column(type="integer")
   * @var int
   */
  private $user_id;

  /**
   * @Column(type="string")
   * @var string
   */
  private $name;

  /**
   * @Column(type="string")
   * @var string|null
   */
  private $value;

  /** @return int */
  public function getUserId() {
    return $this->user_id;
  }

  /** @param int $user_id */
  public function setUserId($user_id) {
    $this->user_id = $user_id;
  }

  /** @return string */
  public function getName() {
    return $this->name;
  }

  /** @param string $name */
  public function setName($name) {
    $this->name = $name;
  }

  /** @return string|null */
  public function getValue() {
    return $this->value;
  }

  /** @param string|null $value */
  public function setValue($value) {
    $this->value = $value;
  }
}
