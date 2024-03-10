<?php declare(strict_types = 1);

namespace MailPoet\Doctrine\EntityTraits;

trait ValidationGroupsTrait {
  /**
   * @var array|null
   */
  private $validationGroups;

  public function getValidationGroups(): ?array {
    return $this->validationGroups;
  }

  public function setValidationGroups(?array $validationGroups): void {
    $this->validationGroups = $validationGroups;
  }
}
