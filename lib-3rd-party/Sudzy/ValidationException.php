<?php

namespace Sudzy;

class ValidationException extends \Exception
{
  protected $_validationErrors;

  public function __construct($errs) {
      $this->_validationErrors = $errs;
      parent::__construct(implode("\n", $errs));
  }

  public function getValidationErrors() {
      return $this->_validationErrors;
  }
}
