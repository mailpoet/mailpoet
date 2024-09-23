<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Validator;

use MailPoet\UnexpectedValueException;
use WP_Error;

class ValidationException extends UnexpectedValueException {
  /** @var WP_Error */
  protected $wpError;

  public static function createFromWpError(WP_Error $wpError): self {
    $exception = self::create()
      ->withMessage($wpError->get_error_message());
    $exception->wpError = $wpError;
    return $exception;
  }

  public function getWpError(): WP_Error {
    return $this->wpError;
  }
}
