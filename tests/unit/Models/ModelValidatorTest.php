<?php

use MailPoet\Models\ModelValidator;

class ModelValidatorTest extends MailPoetTest {
  public $validator;

  function __construct() {
    $this->validator = new ModelValidator();
  }

  function testItConfiguresValidators() {
    $configured_validators = $this->validator->getValidators();
    foreach(array_keys($this->validator->validators) as $validator) {
      expect($configured_validators)->contains($validator);
    }
  }

  function testItValidatesEmail() {
    expect($this->validator->validateEmail('test'))->false();
    expect($this->validator->validateEmail('tést@éxample.com'))->false();
    expect($this->validator->validateEmail('test@example.com'))->true();
  }
}
