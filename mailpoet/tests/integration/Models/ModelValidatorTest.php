<?php declare(strict_types = 1);

namespace MailPoet\Test\Models;

use MailPoet\Models\ModelValidator;

class ModelValidatorTest extends \MailPoetTest {
  public $validator;

  public function __construct() {
    parent::__construct();
    $this->validator = new ModelValidator();
  }

  public function testItConfiguresValidators() {
    $configuredValidators = $this->validator->getValidators();
    foreach (array_keys($this->validator->validators) as $validator) {
      expect($configuredValidators)->contains($validator);
    }
  }

  public function testItValidatesEmail() {
    expect($this->validator->validateEmail('test'))->false();
    expect($this->validator->validateEmail('tést@éxample.com'))->false();
    expect($this->validator->validateEmail('test@example.com'))->true();
    expect($this->validator->validateEmail('loooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong_email@example.com'))->false();
    expect($this->validator->validateEmail('a@b.c'))->false();
  }

  public function testItValidatesNonRoleEmail() {
    expect($this->validator->validateNonRoleEmail('test'))->false();
    expect($this->validator->validateNonRoleEmail('webmaster@example.com'))->false();
    expect($this->validator->validateNonRoleEmail('test@example.com'))->true();
  }

  public function testItValidatesRenderedNewsletterBody() {
    expect($this->validator->validateRenderedNewsletterBody('test'))->false();
    expect($this->validator->validateRenderedNewsletterBody(serialize('test')))->false();
    expect($this->validator->validateRenderedNewsletterBody(['html' => 'test', 'text' => null]))->false();
    expect($this->validator->validateRenderedNewsletterBody(['html' => null, 'text' => 'test']))->false();

    expect($this->validator->validateRenderedNewsletterBody(null))->true();
    expect($this->validator->validateRenderedNewsletterBody(serialize(null)))->true();
    expect($this->validator->validateRenderedNewsletterBody(serialize(['html' => 'test', 'text' => 'test'])))->true();
    expect($this->validator->validateRenderedNewsletterBody(['html' => 'test', 'text' => 'test']))->true();
    expect($this->validator->validateRenderedNewsletterBody(json_encode(null)))->true();
    expect($this->validator->validateRenderedNewsletterBody(json_encode(['html' => 'test', 'text' => 'test'])))->true();
  }
}
