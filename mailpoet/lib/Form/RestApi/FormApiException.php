<?php declare(strict_types = 1);

namespace MailPoet\Form\RestApi;

use Exception as PhpException;
use MailPoet\API\REST\Exception as RestException;
use Throwable;

class FormApiException extends PhpException implements RestException {
  /** @var int */
  private $statusCode;

  /** @var string */
  private $errorCode;

  /** @var array<string, string> */
  private $errors;

  /**
   * @param array<string, string> $errors
   */
  public function __construct(
    string $message,
    int $statusCode = 400,
    string $errorCode = 'mailpoet_forms_error',
    array $errors = [],
    ?Throwable $previous = null
  ) {
    parent::__construct($message, 0, $previous);
    $this->statusCode = $statusCode;
    $this->errorCode = $errorCode;
    $this->errors = $errors;
  }

  public function getStatusCode(): int {
    return $this->statusCode;
  }

  public function getErrorCode(): string {
    return $this->errorCode;
  }

  public function getErrors(): array {
    return $this->errors;
  }
}
