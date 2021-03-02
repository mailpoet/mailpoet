<?php

namespace MailPoet\Entities;

use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="log")
 */
class LogEntity {
  use AutoincrementedIdTrait;
  use CreatedAtTrait;

  /**
   * @ORM\Column(type="string", nullable=true)
   * @var string|null
   */
  private $name;

  /**
   * @ORM\Column(type="integer", nullable=true)
   * @var int|null
   */
  private $level;

  /**
   * @ORM\Column(type="string", nullable=true)
   * @var string|null
   */
  private $message;

  /**
   * @return string|null
   */
  public function getName(): ?string {
    return $this->name;
  }

  /**
   * @return int|null
   */
  public function getLevel(): ?int {
    return $this->level;
  }

  /**
   * @return string|null
   */
  public function getMessage(): ?string {
    return $this->message;
  }
}
