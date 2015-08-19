<?php namespace MailPoet\Models;

class CustomValidator {
  function __construct() {
    $this->validator = new \Sudzy\Engine();
  }

  function init() {
    $this->validator
      ->addValidator('isString', function ($val) {
        return is_string($val);
      });

    return $this->validator;
  }
}
