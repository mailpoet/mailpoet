<?php declare(strict_types = 1);

namespace MailPoet\Test\Mailer\Methods\ErrorMappers;

use Codeception\Stub;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Methods\ErrorMappers\PHPMailMapper;
use MailPoet\WP\Functions as WPFunctions;

class PHPMailMapperTest extends \MailPoetUnitTest {

  /** @var PHPMailMapper*/
  private $mapper;

  public function _before() {
    parent::_before();
    $this->mapper = new PHPMailMapper();
    WPFunctions::set(Stub::make(new WPFunctions, [
      '__' => function ($value) {
        return $value;
      },
    ]));
  }

  public function testGetProperErrorForSubscriber() {
    $error = $this->mapper->getErrorForSubscriber('john@rambo.com');
    expect($error->getLevel())->equals(MailerError::LEVEL_HARD);
    expect($error->getMessage())->equals('PHPMail has returned an unknown error.');
    expect($error->getSubscriberErrors()[0]->getEmail())->equals('john@rambo.com');
  }

  public function testGetProperErrorFromException() {
    $error = $this->mapper->getErrorFromException(new \Exception('Some message'), 'john@rambo.com');
    expect($error->getLevel())->equals(MailerError::LEVEL_HARD);
    expect($error->getMessage())->equals('Some message');
    expect($error->getSubscriberErrors()[0]->getEmail())->equals('john@rambo.com');
  }

  public function testGetSoftErrorFromExceptionForInvalidEmail() {
    $error = $this->mapper->getErrorFromException(new \Exception('Invalid address. (Add ...'), 'john@rambo.com');
    expect($error->getLevel())->equals(MailerError::LEVEL_SOFT);
  }

  public function _after() {
    parent::_after();
    WPFunctions::set(new WPFunctions);
  }
}
