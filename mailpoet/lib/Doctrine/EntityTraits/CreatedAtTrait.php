<?php

namespace MailPoet\Doctrine\EntityTraits;

use DateTimeInterface;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

trait CreatedAtTrait {
  /**
   * @ORM\Column(type="datetimetz")
   * @var DateTimeInterface
   */
  private $createdAt;

  /** @return DateTimeInterface */
  public function getCreatedAt() {
    return $this->createdAt;
  }

  public function setCreatedAt(DateTimeInterface $createdAt) {
    $this->createdAt = $createdAt;
  }
}
