<?php

namespace MailPoet\Doctrine;

use MailPoetVendor\Symfony\Component\Validator\ConstraintViolationInterface;
use MailPoetVendor\Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationException extends \RuntimeException {
  /** @var string */
  private $resource_name;

  /** @var ConstraintViolationListInterface|ConstraintViolationInterface[] */
  private $violations;

  function __construct($resource_name, ConstraintViolationListInterface $violations) {
    $this->resource_name = $resource_name;
    $this->violations = $violations;

    $line_prefix = '  ';
    $message = "Validation failed for '$resource_name'.\nDetails:\n";
    $message .= $line_prefix . implode("\n$line_prefix", $this->getErrors());
    parent::__construct($message);
  }

  /** @return string */
  function getResourceName() {
    return $this->resource_name;
  }

  /** @return ConstraintViolationListInterface|ConstraintViolationInterface[] */
  function getViolations() {
    return $this->violations;
  }

  /** @return string[] */
  function getErrors() {
    $messages = [];
    foreach ($this->violations as $violation) {
      $messages[] = $this->formatError($violation);
    }
    sort($messages);
    return $messages;
  }

  private function formatError(ConstraintViolationInterface $violation) {
    return '[' . $violation->getPropertyPath() . '] ' . $violation->getMessage();
  }
}
