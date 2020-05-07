<?php declare(strict_types = 1);

// phpcs:ignoreFile PSR1.Classes.ClassDeclaration
namespace MailPoet;

/**
 * Frames all MailPoet exceptions ("$e instanceof MailPoet\Exception").
 */
abstract class Exception extends \Exception {
  /** @var string[] */
  private $errors = [];

  final public function __construct($message = '', $code = 0, \Throwable $previous = null) {
    parent::__construct($message, $code, $previous);
  }

  public static function create(\Throwable $previous = null): self {
    return new static('', 0, $previous);
  }

  public function withMessage(string $message): self {
    $this->message = $message;
    return $this;
  }

  public function withCode(int $code): self {
    $this->code = $code;
    return $this;
  }

  public function withErrors(array $errors): self {
    $this->errors = $errors;
    return $this;
  }

  public function getErrors(): array {
    return $this->errors;
  }
}


/**
 * USE: Generic runtime error. When possible, use a more specific exception instead.
 */
class RuntimeException extends Exception {}


/**
 * USE: When wrong data VALUE is received.
 */
class UnexpectedValueException extends RuntimeException {}


/**
 * USE: When an action is forbidden for given actor (although generally valid).
 */
class AccessDeniedException extends UnexpectedValueException {}


/**
 * USE: When the main resource we're interested in doesn't exist.
 */
class NotFoundException extends UnexpectedValueException {}


/**
 * USE: When the main action produces conflict (i.e. duplicate key).
 */
class ConflictException extends UnexpectedValueException {}


/**
 * USE: An application state that should not occur. Can be subclassed for feature-specific exceptions.
 */
class InvalidStateException extends RuntimeException {}
