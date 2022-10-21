<?php declare(strict_types = 1);

namespace MailPoet\Validator;

use MailPoet\UnexpectedValueException;
use WP_Error;

class ValidationException extends UnexpectedValueException {
  /** @var WP_Error */
  protected $wpError;

  public static function createFromWpError(WP_Error $wpError): self {
    $exception = self::create();
    foreach ($wpError->errors as $code => $error) {
      $exception->withError($code, current($error));
    }
    $exception->wpError = $wpError;
    return $exception;
  }

  public function getWpError(): WP_Error {
    return $this->wpError;
  }
}
