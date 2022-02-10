<?php declare(strict_types = 1);

namespace MailPoet\Automation;

use MailPoet\Automation\API\API;

class Automation {
  /** @var API */
  private $api;

  public function __construct(
    API $api
  ) {
    $this->api = $api;
  }

  public function initialize(): void {
    $this->api->initialize();
  }
}
