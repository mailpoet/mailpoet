<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

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

    try {
      $this->validator->validate($token);
    } catch (\Exception $e) {
      throw new ValidationError($e->getMessage());
    }

    return true;
  }
}
