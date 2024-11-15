<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Response;

class RedirectResponse extends Response {

  public function __construct($location) { // phpcs:ignore
    parent::__construct(self::REDIRECT, [], $location);
  }

  public function getData() {
    return [];
  }
}
