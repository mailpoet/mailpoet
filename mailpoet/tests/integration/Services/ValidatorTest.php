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
    verify($this->validator->validateEmail('test'))->false();
    verify($this->validator->validateEmail('tést@éxample.com'))->false();
    verify($this->validator->validateEmail('test@example.com'))->true();
    verify($this->validator->validateEmail('loooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong_email@example.com'))->false();
    verify($this->validator->validateEmail('a@b.c'))->false();
  }

  public function testItValidatesNonRoleEmail() {
    verify($this->validator->validateNonRoleEmail('test'))->false();
    verify($this->validator->validateNonRoleEmail('webmaster@example.com'))->false();
    verify($this->validator->validateNonRoleEmail('test@example.com'))->true();
  }
}
