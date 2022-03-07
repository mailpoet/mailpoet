<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine;

use MailPoet\Automation\Engine\API\API;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;

class Engine {
  /** @var API */
  private $api;

  /** @var Registry */
  private $registry;

  /** @var WordPress */
  private $wordPress;

  /** @var WorkflowStorage */
  private $workflowStorage;

  public function __construct(
    API $api,
    Registry $registry,
    WordPress $wordPress,
    WorkflowStorage $workflowStorage
  ) {
    $this->api = $api;
    $this->registry = $registry;
    $this->wordPress = $wordPress;
    $this->workflowStorage = $workflowStorage;
  }

  public function initialize(): void {
    // register Action Scheduler (when behind feature flag, do it only on initialization)
    require_once __DIR__ . '/../../../vendor/woocommerce/action-scheduler/action-scheduler.php';

    $this->api->initialize();

    $this->wordPress->doAction(Hooks::INITIALIZE, [$this->registry]);
    $this->registerActiveTriggerHooks();
  }

  private function registerActiveTriggerHooks(): void {
    $triggerKeys = $this->workflowStorage->getActiveTriggerKeys();
    foreach ($triggerKeys as $triggerKey) {
      $instance = $this->registry->getTrigger($triggerKey);
      if ($instance) {
        $instance->registerHooks();
      }
    }
  }
}
