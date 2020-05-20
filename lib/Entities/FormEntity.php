<?php

namespace MailPoet\Entities;

use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\DeletedAtTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="forms")
 */
class FormEntity {
  use AutoincrementedIdTrait;
  use CreatedAtTrait;
  use UpdatedAtTrait;
  use DeletedAtTrait;

  const DISPLAY_TYPE_BELOW_POST = 'below_post';
  const DISPLAY_TYPE_FIXED_BAR = 'fixed_bar';
  const DISPLAY_TYPE_POPUP = 'popup';
  const DISPLAY_TYPE_SLIDE_IN = 'slide_in';
  const DISPLAY_TYPE_OTHERS = 'others';

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $name;

  /**
   * @ORM\Column(type="serialized_array")
   * @var array|null
   */
  private $body;

  /**
   * @ORM\Column(type="serialized_array")
   * @var array|null
   */
  private $settings;

  /**
   * @ORM\Column(type="string")
   * @var string|null
   */
  private $styles;

  public function __construct($name) {
    $this->name = $name;
  }

  /**
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @return array|null
   */
  public function getBody() {
    return $this->body;
  }

  /**
   * @return array|null
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * @return string|null
   */
  public function getStyles() {
    return $this->styles;
  }

  /**
   * @param string $name
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * @param array|null $body
   */
  public function setBody($body) {
    $this->body = $body;
  }

  /**
   * @param array|null $settings
   */
  public function setSettings($settings) {
    $this->settings = $settings;
  }

  /**
   * @param string|null $styles
   */
  public function setStyles($styles) {
    $this->styles = $styles;
  }

  public function toArray(): array {
    return [
      'id' => $this->getId(),
      'name' => $this->getName(),
      'body' => $this->getBody(),
      'settings' => $this->getSettings(),
      'styles' => $this->getStyles(),
      'created_at' => $this->getCreatedAt(),
      'updated_at' => $this->getUpdatedAt(),
      'deleted_at' => $this->getDeletedAt(),
    ];
  }
}
