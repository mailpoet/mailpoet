<?php declare(strict_types = 1);

namespace MailPoet\Test\Doctrine\Types;

use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="test_json_entity")
 */
class JsonEntity {
  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue
   * @var int|null
   */
  private $id;

  /**
   * @ORM\Column(type="json")
   * @var array|null
   */
  private $jsonData;

  /**
   * @ORM\Column(type="json_or_serialized")
   * @var array|null
   */
  private $jsonOrSerializedData;

  /**
   * @return int|null
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @return array|null
   */
  public function getJsonData() {
    return $this->jsonData;
  }

  /**
   * @param array|null $jsonData
   */
  public function setJsonData($jsonData) {
    $this->jsonData = $jsonData;
  }

  /**
   * @return array|null
   */
  public function getJsonOrSerializedData() {
    return $this->jsonOrSerializedData;
  }

  /**
   * @param array|null $jsonOrSerializedData
   */
  public function setJsonOrSerializedData($jsonOrSerializedData) {
    $this->jsonOrSerializedData = $jsonOrSerializedData;
  }
}
