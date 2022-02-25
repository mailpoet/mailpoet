<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine;

use MailPoet\Automation\Engine\API\API;

class Engine {
  /** @var API */
  private $api;

  public function __construct(
    API $api
  ) {
    $this->api = $api;
  }

  public function initialize(): void {
    // register Action Scheduler (when behind feature flag, do it only on initialization)
    require_once __DIR__ . '/../../../vendor/woocommerce/action-scheduler/action-scheduler.php';

    $this->api->initialize();
  }
}
