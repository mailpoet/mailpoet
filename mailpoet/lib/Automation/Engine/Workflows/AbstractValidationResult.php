<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Workflows;

abstract class AbstractValidationResult {
  /**
   * @var array
   */
  private $errors = [];

  public function addError(string $key, string $error): void {
    $this->errors[$key] = $error;
  }
  
  public function getErrors(): array {
    return $this->errors;
  }
  
  public function hasErrors(): bool {
    return !empty($this->getErrors());
  }
  
  abstract public function isValid(): bool;
}
