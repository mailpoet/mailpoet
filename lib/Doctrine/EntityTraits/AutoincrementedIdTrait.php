<?php

namespace MailPoet\Doctrine\EntityTraits;

use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

trait AutoincrementedIdTrait {
  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue
   * @var int|null
   */
  private $id;

  /** @return int */
  public function getId() {
    return $this->id;
  }
}
