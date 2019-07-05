<?php

namespace MailPoet\Doctrine\EntityTraits;

use DateTimeInterface;

trait UpdatedAtTrait {
  /**
   * @Column(type="datetimetz")
   * @var DateTimeInterface
   */
  private $updated_at;

  /** @return DateTimeInterface */
  public function getUpdatedAt() {
    return $this->updated_at;
  }

  public function setUpdatedAt(DateTimeInterface $updated_at) {
    $this->updated_at = $updated_at;
  }
}
