<?php

namespace MailPoet\Entities;

use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;
use MailPoetVendor\Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(name="custom_fields")
 */
class CustomFieldEntity {
  use AutoincrementedIdTrait;
  use CreatedAtTrait;
  use UpdatedAtTrait;

  /**
   * @ORM\Column(type="string", nullable=false, unique=true)
   * @Assert\NotBlank()
   * @var string
   */
  private $name;

  /**
   * @ORM\Column(type="string", nullable=false)
   * @Assert\NotBlank()
   * @var string
   */
  private $type;

  /**
   * @ORM\Column(type="array")
   * @var array
   */
  private $params;

  /**
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @return string
   */
  public function getType() {
    return $this->type;
  }

  /**
   * @return array
   */
  public function getParams() {
    return $this->params;
  }

  /**
   * @param string $name
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * @param string $type
   */
  public function setType($type) {
    $this->type = $type;
  }

  /**
   * @param array $params
   */
  public function setParams(array $params) {
    $this->params = $params;
  }
}
