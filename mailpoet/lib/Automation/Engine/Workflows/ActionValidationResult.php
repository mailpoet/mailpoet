<?php

namespace MailPoet\Automation\Engine\Workflows;

class ActionValidationResult {
  private $errors = [];
  private $validated = [];

  public function setValidatedParam(string $key, $value): void {
    $this->validated[$key] = $value;
  }

  public function addError(\Exception $exception): void {
    $this->errors[] = $exception;
  }

  public function isValid(): bool {
    return count($this->getErrors()) === 0;
  }

  public function getErrors(): array {
    return $this->errors;
  }

  public function hasErrors(): bool {
    return count($this->getErrors()) > 0;
  }

  public function getValidatedParams(): array {
    return $this->validated;
  }

  public function getValidatedParam(string $key) {
    return $this->getValidatedParams()[$key] ?? null;
  }
}