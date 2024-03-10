<?php declare(strict_types = 1);

namespace MailPoet\Test\Doctrine\EventListeners;

use MailPoet\Doctrine\EntityTraits\ValidationGroupsTrait;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;
use MailPoetVendor\Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(name="test_validated_entity")
 */
class ValidatedEntity {
  use ValidationGroupsTrait;

  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue
   * @var int|null
   */
  private $id;

  /**
   * @ORM\Column(type="string")
   * @Assert\NotBlank()
   * @Assert\Length(min=3)
   * @var string
   */
  private $name;

  /**
   * @ORM\Column(type="string")
   * @Assert\Email(groups={"Saving"})
   * @var string
   */
  private $email;

  /** @return int|null */
  public function getId() {
    return $this->id;
  }

  /** @return string */
  public function getName() {
    return $this->name;
  }

  /** @param string $name */
  public function setName($name) {
    $this->name = $name;
  }

  /** @return string */
  public function getEmail() {
    return $this->email;
  }

  /** @param string $email */
  public function setEmail($email) {
    $this->email = $email;
  }
}
