<?php

namespace MailPoet\Doctrine\EntityTraits;

use DateTimeInterface;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

trait CreatedAtTrait {
  /**
   * @ORM\Column(type="datetimetz")
   * @var DateTimeInterface
   */
  private $created_at;

  /** @return DateTimeInterface */
  public function getCreatedAt() {
    return $this->created_at;
  }

  public function setCreatedAt(DateTimeInterface $created_at) {
    $this->created_at = $created_at;
  }
}
