<?php
namespace MailPoet\Test\Mailer\Methods\ErrorMappers;

use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Methods\ErrorMappers\PHPMailMapper;

class PHPMailMapperTest extends \MailPoetTest {

  /** @var PHPMailMapper*/
  private $mapper;

  function _before() {
    parent::_before();
    $this->mapper = new PHPMailMapper();
  }

  function testGetProperErrorForSubscriber() {
    $error = $this->mapper->getErrorForSubscriber('john@rambo.com');
    expect($error->getLevel())->equals(MailerError::LEVEL_HARD);
    expect($error->getMessage())->equals('PHPMail has returned an unknown error.');
    expect($error->getSubscriberErrors()[0]->getEmail())->equals('john@rambo.com');
  }

  function testGetProperErrorFromException() {
    $error = $this->mapper->getErrorFromException(new \Exception('Some message'), 'john@rambo.com');
    expect($error->getLevel())->equals(MailerError::LEVEL_HARD);
    expect($error->getMessage())->equals('Some message');
    expect($error->getSubscriberErrors()[0]->getEmail())->equals('john@rambo.com');
  }

  function testGetSoftErrorFromExceptionForInvalidEmail() {
    $error = $this->mapper->getErrorFromException(new \Exception('Invalid address. (Add ...'), 'john@rambo.com');
    expect($error->getLevel())->equals(MailerError::LEVEL_SOFT);
  }
}
