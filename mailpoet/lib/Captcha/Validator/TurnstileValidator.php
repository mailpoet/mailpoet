<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Captcha\Validator;

use MailPoet\Captcha\TurnstileValidator as Validator;

class TurnstileValidator {

  /** @var Validator */
  private $validator;

  public function __construct(
    Validator $validator
  ) {
    $this->validator = $validator;
  }

  public function validate(array $data): bool {
    $token = $data['turnstileResponseToken'] ?? '';
    if (!is_string($token)) {
      throw new ValidationError(__('CAPTCHA verification failed.', 'mailpoet'));
    }

    try {
      $this->validator->validate($token);
    } catch (\Throwable $e) {
      throw new ValidationError($e->getMessage(), [], 0, $e);
    }

    return true;
  }
}
