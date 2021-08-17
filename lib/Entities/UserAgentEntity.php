<?php

namespace MailPoet\Entities;

use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="user_agents")
 */
class UserAgentEntity {
  use AutoincrementedIdTrait;
  use CreatedAtTrait;
  use UpdatedAtTrait;

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $hash;

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $userAgent;

  public function __construct(string $userAgent) {
    $this->setUserAgent($userAgent);
  }

  public function getUserAgent(): string {
    return $this->userAgent;
  }

  public function setUserAgent(string $userAgent): void {
    $this->userAgent = $userAgent;
    $this->hash = (string)crc32($userAgent);
  }

  public function getHash(): string {
    return $this->hash;
  }
}
