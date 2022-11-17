<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Entities;

use DateTimeInterface;
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

  public function setName(?string $name): void {
    $this->name = $name;
  }

  public function setLevel(?int $level): void {
    $this->level = $level;
  }

  public function setMessage(?string $message): void {
    $this->message = $message;
  }

  public function setCreatedAt(DateTimeInterface $createdAt): void {
    $this->createdAt = $createdAt;
  }
}
