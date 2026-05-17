<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use RuntimeException;
use Throwable;

/**
 * Domain-level error raised by {@see BulkActionController}. The HTTP transport
 * layer (REST endpoint or legacy JSON endpoint) is responsible for translating
 * the carried status code + error code into its own response envelope.
 */
class BulkActionException extends RuntimeException {
  /** @var string */
  private $errorCode;

  /** @var int */
  private $statusCode;

  public function __construct(
    string $message,
    string $errorCode,
    int $statusCode = 400,
    ?Throwable $previous = null
  ) {
    parent::__construct($message, 0, $previous);
    $this->errorCode = $errorCode;
    $this->statusCode = $statusCode;
  }

  public function getErrorCode(): string {
    return $this->errorCode;
  }

  public function getStatusCode(): int {
    return $this->statusCode;
  }
}
