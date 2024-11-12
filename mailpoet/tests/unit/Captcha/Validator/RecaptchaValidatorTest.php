<?php declare(strict_types = 1);

namespace MailPoet\Captcha\Validator;

use Codeception\Stub;
use MailPoet\Captcha\ReCaptchaValidator as Validator;

class RecaptchaValidatorTest extends \MailPoetUnitTest {
  public function testSuccessfulValidation() {
    $responseToken = 'recaptchaResponseToken';
    $validator = Stub::make(
      Validator::class,
      [
        'validate' => function ($responseToken) {
          return null;
        },
      ],
      $this
    );

    $testee = new RecaptchaValidator($validator);
    $data = [
      'recaptchaResponseToken' => $responseToken,
    ];

    verify($testee->validate($data))->true();
  }

  public function testFailingValidation() {
    $responseToken = 'recaptchaResponseToken';
    $exceptionErr = 'Error while validating the CAPTCHA.';
    $validator = Stub::make(
      Validator::class,
      [
        'validate' => function ($responseToken) use ($exceptionErr) {
          throw new \Exception($exceptionErr);
        },
      ],
      $this
    );

    $testee = new RecaptchaValidator($validator);
    $data = [
      'recaptchaResponseToken' => $responseToken,
    ];

    try {
      $testee->validate($data);
    } catch (\Exception $e) {
      verify($e)->instanceOf(ValidationError::class);
      verify($e->getMessage())->equals($exceptionErr);
    }
  }
}
