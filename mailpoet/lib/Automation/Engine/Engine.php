<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine;

use MailPoet\Automation\Engine\API\API;

class Engine {
  /** @var API */
  private $api;

  /** @var Registry */
  private $registry;

  /** @var WordPress */
  private $wordPress;

  public function __construct(
    API $api,
    Registry $registry,
    WordPress $wordPress
  ) {
    $this->api = $api;
    $this->registry = $registry;
    $this->wordPress = $wordPress;
  }

  public function initialize(): void {
    // register Action Scheduler (when behind feature flag, do it only on initialization)
    require_once __DIR__ . '/../../../vendor/woocommerce/action-scheduler/action-scheduler.php';

    $this->api->initialize();

    $this->wordPress->doAction(Hooks::INITIALIZE, [$this->registry]);
  }
}
