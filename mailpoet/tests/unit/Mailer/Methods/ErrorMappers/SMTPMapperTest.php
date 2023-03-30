<?php declare(strict_types = 1);

namespace MailPoet\Test\Mailer\Methods\ErrorMappers;

use Codeception\Stub;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Methods\ErrorMappers\SMTPMapper;
use MailPoet\Mailer\WordPress\PHPMailerLoader;
use MailPoet\WP\Functions as WPFunctions;
use PHPMailer\PHPMailer\Exception;

PHPMailerLoader::load();

class SMTPMapperTest extends \MailPoetUnitTest {

  /** @var SMTPMapper */
  private $mapper;

  public function _before() {
    parent::_before();
    $this->mapper = new SMTPMapper();
    WPFunctions::set(Stub::make(new WPFunctions, [
      '__' => function ($value) {
        return $value;
      },
    ]));
  }

  public function testItCreatesSoftErrorForInvalidEmail() {
    $message = 'Invalid address (to): john@rambo.com';
    $error = $this->mapper->getErrorFromException(new Exception($message), 'john@rambo.com');
    expect($error->getLevel())->equals(MailerError::LEVEL_SOFT);
  }

  public function _after() {
    parent::_after();
    WPFunctions::set(new WPFunctions);
  }
}
