<?php declare(strict_types = 1);

namespace MailPoet\CustomFields\RestApi;

use MailPoet\API\REST\ApiException;
use Throwable;

class CustomFieldApiException extends ApiException {
  /**
   * @param array<string, string> $errors
   */
  public function __construct(
    string $message,
    int $statusCode = 400,
    string $errorCode = 'mailpoet_custom_fields_error',
    array $errors = [],
    ?Throwable $previous = null
  ) {
    parent::__construct($message, $statusCode, $errorCode, $errors, $previous);
  }
}
