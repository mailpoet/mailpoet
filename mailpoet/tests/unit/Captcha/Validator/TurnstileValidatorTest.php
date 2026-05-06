<?php declare(strict_types = 1);

namespace MailPoet\Captcha\Validator;

use Codeception\Stub;
use MailPoet\Captcha\TurnstileValidator as Validator;

class TurnstileValidatorTest extends \MailPoetUnitTest {
  public function testSuccessfulValidation() {
    $responseToken = 'turnstileResponseToken';
    $validator = Stub::make(
      Validator::class,
      [
        'validate' => function ($token) use ($responseToken) {
          verify($token)->equals($responseToken);
          return null;
        },
      ],
      $this
    );

    $testee = new TurnstileValidator($validator);
    $data = [
      'turnstileResponseToken' => $responseToken,
    ];

    verify($testee->validate($data))->true();
  }

  public function testFailingValidation() {
    $responseToken = 'turnstileResponseToken';
    $exceptionErr = 'Error while validating the CAPTCHA.';
    $validator = Stub::make(
      Validator::class,
      [
        'validate' => function ($token) use ($exceptionErr, $responseToken) {
          verify($token)->equals($responseToken);
          throw new \Exception($exceptionErr);
        },
      ],
      $this
    );

    $testee = new TurnstileValidator($validator);
    $data = [
      'turnstileResponseToken' => $responseToken,
    ];

    $this->expectException(ValidationError::class);
    $this->expectExceptionMessage($exceptionErr);
    $testee->validate($data);
  }

  public function testItFailsIfTokenIsNotString() {
    $validator = Stub::make(
      Validator::class,
      [
        'validate' => Stub\Expected::never(),
      ],
      $this
    );

    $testee = new TurnstileValidator($validator);
    $data = [
      'turnstileResponseToken' => ['unexpectedToken'],
    ];

    $this->expectException(ValidationError::class);
    $this->expectExceptionMessage('CAPTCHA verification failed.');
    $testee->validate($data);
  }

  public function testItConvertsThrowableToValidationError() {
    $error = 'Unexpected CAPTCHA validation failure.';
    $validator = Stub::make(
      Validator::class,
      [
        'validate' => function () use ($error) {
          throw new \TypeError($error);
        },
      ],
      $this
    );

    $testee = new TurnstileValidator($validator);
    $data = [
      'turnstileResponseToken' => 'turnstileResponseToken',
    ];

    $this->expectException(ValidationError::class);
    $this->expectExceptionMessage($error);
    $testee->validate($data);
  }
}
