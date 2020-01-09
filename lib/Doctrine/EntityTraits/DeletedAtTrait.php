<?php

namespace MailPoet\Doctrine\EntityTraits;

use DateTimeInterface;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

trait DeletedAtTrait {
  /**
   * @ORM\Column(type="datetimetz", nullable=true)
   * @var DateTimeInterface|null
   */
  private $deleted_at;

  /** @return DateTimeInterface|null */
  public function getDeletedAt() {
    return $this->deletedAt;
  }

  /** @param DateTimeInterface|null $deleted_at */
  public function setDeletedAt($deletedAt) {
    $this->deletedAt = $deletedAt;
  }
}
