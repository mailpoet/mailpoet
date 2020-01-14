<?php

namespace MailPoet\Doctrine\EntityTraits;

use DateTimeInterface;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

trait UpdatedAtTrait {
  /**
   * @ORM\Column(type="datetimetz")
   * @var DateTimeInterface
   */
  private $updatedAt;

  /** @return DateTimeInterface */
  public function getUpdatedAt() {
    return $this->updatedAt;
  }

  public function setUpdatedAt(DateTimeInterface $updatedAt) {
    $this->updatedAt = $updatedAt;
  }
}
