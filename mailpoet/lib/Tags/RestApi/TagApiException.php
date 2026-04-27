<?php declare(strict_types = 1);

namespace MailPoet\Tags\RestApi;

use MailPoet\API\REST\ApiException;
use Throwable;

class TagApiException extends ApiException {
  /**
   * @param array<string, string> $errors
   */
  public function __construct(
    string $message,
    int $statusCode = 400,
    string $errorCode = 'mailpoet_tags_error',
    array $errors = [],
    ?Throwable $previous = null
  ) {
    parent::__construct($message, $statusCode, $errorCode, $errors, $previous);
  }
}
