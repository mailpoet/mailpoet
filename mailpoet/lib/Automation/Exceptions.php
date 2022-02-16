<?php declare(strict_types = 1);

namespace MailPoet\Automation;

use MailPoet\Automation\Exceptions\InvalidStateException;

class Exceptions {
  public function __construct() {
    throw new InvalidStateException(
      "This is a static factory class. Use it via 'Exception::someError()' factories."
    );
  }
}
