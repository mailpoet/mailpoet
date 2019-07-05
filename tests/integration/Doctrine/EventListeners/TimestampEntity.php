<?php

namespace MailPoet\Test\Doctrine\EventListeners;

use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;

/**
 * @Entity()
 * @Table(name="test_timestamp_entity")
 */
class TimestampEntity {
  use CreatedAtTrait;
  use UpdatedAtTrait;

  /**
   * @Column(type="integer")
   * @Id
   * @GeneratedValue
   * @var int|null
   */
  private $id;

  /**
   * @Column(type="string")
   * @var string
   */
  private $name;

  /** @return int|null */
  function getId() {
    return $this->id;
  }

  /** @return string */
  function getName() {
    return $this->name;
  }

  /** @param string $name */
  function setName($name) {
    $this->name = $name;
  }
}
