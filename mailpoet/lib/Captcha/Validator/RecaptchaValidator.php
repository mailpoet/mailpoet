<?php declare(strict_types = 1);

namespace MailPoet\Captcha\Validator;

use MailPoet\Captcha\ReCaptchaValidator as Validator;

class RecaptchaValidator {

  /** @var Validator */
  private $validator;

  public function __construct(
    Validator $validator
  ) {
    $this->validator = $validator;
  }

  public function validate(array $data): bool {
    $token = $data['recaptchaResponseToken'] ?? '';
    if (!is_string($token)) {
      throw new ValidationError(__('CAPTCHA verification failed.', 'mailpoet'));
    }

    try {
      $this->validator->validate($token);
    } catch (\Exception $e) {
      throw new ValidationError($e->getMessage());
    }

    return true;
  }
}
