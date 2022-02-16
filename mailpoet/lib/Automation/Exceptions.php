<?php declare(strict_types = 1);

namespace MailPoet\Automation;

use MailPoet\Automation\Exceptions\InvalidStateException;
use MailPoet\Automation\Exceptions\UnexpectedValueException;

class Exceptions {
  private const API_NO_JSON_BODY = 'mailpoet_automation_api_no_json_body';

  public function __construct() {
    throw new InvalidStateException(
      "This is a static factory class. Use it via 'Exception::someError()' factories."
    );
  }

  public static function apiNoJsonBody(): UnexpectedValueException {
    return UnexpectedValueException::create()
      ->withErrorCode(self::API_NO_JSON_BODY)
      ->withMessage('No JSON body passed.');
  }
}
