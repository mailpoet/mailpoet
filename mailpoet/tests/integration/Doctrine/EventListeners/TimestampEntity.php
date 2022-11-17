<?php declare(strict_types = 1);

namespace MailPoet\Test\Doctrine\EventListeners;

use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="test_timestamp_entity")
 */
class TimestampEntity {
  use CreatedAtTrait;
  use UpdatedAtTrait;

  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue
   * @var int|null
   */
  private $id;

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $name;

  /** @return int|null */
  public function getId() {
    return $this->id;
  }

  /** @return string */
  public function getName() {
    return $this->name;
  }

  /** @param string $name */
  public function setName($name) {
    $this->name = $name;
  }
}
