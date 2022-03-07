<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine;

use MailPoet\Automation\Engine\Exceptions\InvalidStateException;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;

class Exceptions {
  private const MIGRATION_FAILED = 'mailpoet_automation_migration_failed';
  private const DATABASE_ERROR = 'mailpoet_automation_database_error';
  private const API_METHOD_NOT_ALLOWED = 'mailpoet_automation_api_method_not_allowed';
  private const API_NO_JSON_BODY = 'mailpoet_automation_api_no_json_body';

  public function __construct() {
    throw new InvalidStateException(
      "This is a static factory class. Use it via 'Exception::someError()' factories."
    );
  }

  public static function migrationFailed(string $error): InvalidStateException {
    return InvalidStateException::create()
      ->withErrorCode(self::MIGRATION_FAILED)
      ->withMessage(__(sprintf('Migration failed: %s', $error), 'mailpoet'));
  }

  public static function databaseError(string $error): InvalidStateException {
    return InvalidStateException::create()
      ->withErrorCode(self::DATABASE_ERROR)
      ->withMessage(__(sprintf('Database error: %s', $error), 'mailpoet'));
  }

  public static function apiMethodNotAllowed(): UnexpectedValueException {
    return UnexpectedValueException::create()
      ->withStatusCode(405)
      ->withErrorCode(self::API_METHOD_NOT_ALLOWED)
      ->withMessage(__('Method not allowed.', 'mailpoet'));
  }

  public static function apiNoJsonBody(): UnexpectedValueException {
    return UnexpectedValueException::create()
      ->withErrorCode(self::API_NO_JSON_BODY)
      ->withMessage(__('No JSON body passed.', 'mailpoet'));
  }
}
