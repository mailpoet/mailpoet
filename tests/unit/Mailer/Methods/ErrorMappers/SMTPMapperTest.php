<?php

namespace MailPoet\Test\Mailer\Methods\ErrorMappers;

use Codeception\Stub;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Methods\ErrorMappers\SMTPMapper;
use MailPoet\WP\Functions as WPFunctions;

class SMTPMapperTest extends \MailPoetUnitTest {

  /** @var SMTPMapper */
  private $mapper;

  function _before() {
    parent::_before();
    $this->mapper = new SMTPMapper();
    WPFunctions::set(Stub::make(new WPFunctions, [
      '__' => function ($value) {
        return $value;
      },
    ]));
  }

  function testItCanProcessExceptionMessage() {
    $message = 'Connection could not be established with host localhost [Connection refused #111]' . PHP_EOL
      . 'Log data:' . PHP_EOL
      . '++ Starting Swift_SmtpTransport' . PHP_EOL
      . '!! Connection could not be established with host localhost [Connection refused #111] (code: 0)';
    $error = $this->mapper->getErrorFromException(new \Exception($message), 'john@rambo.com');
    expect($error->getMessage())
      ->equals('Connection could not be established with host localhost [Connection refused #111]');
    expect($error->getLevel())->equals(MailerError::LEVEL_HARD);
    expect($error->getSubscriberErrors()[0]->getEmail())->equals('john@rambo.com');
  }

  function testItCreatesSoftErrorForInvalidEmail() {
    $message = 'Invalid email';
    $error = $this->mapper->getErrorFromException(new \Swift_RfcComplianceException($message), 'john@rambo.com');
    expect($error->getLevel())->equals(MailerError::LEVEL_SOFT);
  }

  function testItCanProcessLogMessageWhenOneExists() {
    $log = '++ Swift_SmtpTransport started' . PHP_EOL
      . '>> MAIL FROM:<moi@mrcasual.com>' . PHP_EOL
      . '<< 250 OK' . PHP_EOL
      . '>> RCPT TO:<test2@ietsdoenofferte.nl>' . PHP_EOL
      . '<< 550 No such recipient here' . PHP_EOL
      . '!! Expected response code 250/251/252 but got code "550", with message "550 No such recipient here' . PHP_EOL
      . '" (code: 550)' . PHP_EOL
      . '>> RSET' . PHP_EOL
      . '<< 250 Reset OK' . PHP_EOL;
    $error = $this->mapper->getErrorFromLog($log, 'test@example.com');
    expect($error->getMessage())
      ->equals('Expected response code 250/251/252 but got code "550", with message "550 No such recipient here" (code: 550)');
    expect($error->getSubscriberErrors()[0]->getEmail('moi@mrcasual.com'));
  }

  function testItReturnsGenericMessageWhenLogMessageDoesNotExist() {
    $error = $this->mapper->getErrorFromLog(null, 'test@example.com');
    expect($error->getMessage())
      ->equals(Mailer::METHOD_SMTP . ' has returned an unknown error.');
    expect($error->getSubscriberErrors()[0]->getEmail('moi@mrcasual.com'));
  }

  function _after() {
    WPFunctions::set(new WPFunctions);
  }
}
