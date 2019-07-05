<?php

namespace MailPoet\Doctrine\EntityTraits;

use DateTimeInterface;

trait CreatedAtTrait {
  /**
   * @Column(type="datetimetz")
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
