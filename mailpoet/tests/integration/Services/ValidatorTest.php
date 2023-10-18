<?php declare(strict_types = 1);

namespace MailPoet\Test\Services;

use MailPoet\Services\Validator;

class ValidatorTest extends \MailPoetTest {
  public $validator;

  public function _before() {
    parent::_before();
    $this->validator = $this->diContainer->get(Validator::class);
  }

  public function testItValidatesEmail() {
    expect($this->validator->validateEmail('test'))->false();
    expect($this->validator->validateEmail('tést@éxample.com'))->false();
    verify($this->validator->validateEmail('test@example.com'))->true();
    expect($this->validator->validateEmail('loooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong_email@example.com'))->false();
    expect($this->validator->validateEmail('a@b.c'))->false();
  }

  public function testItValidatesNonRoleEmail() {
    expect($this->validator->validateNonRoleEmail('test'))->false();
    expect($this->validator->validateNonRoleEmail('webmaster@example.com'))->false();
    verify($this->validator->validateNonRoleEmail('test@example.com'))->true();
  }
}
