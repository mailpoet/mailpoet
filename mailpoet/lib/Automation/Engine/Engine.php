<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine;

use MailPoet\Automation\Engine\API\API;
use MailPoet\Automation\Engine\Control\StepRunner;
use MailPoet\Automation\Engine\Control\TriggerHandler;
use MailPoet\Automation\Engine\Endpoints\System\DatabaseDeleteEndpoint;
use MailPoet\Automation\Engine\Endpoints\System\DatabasePostEndpoint;
use MailPoet\Automation\Engine\Endpoints\Workflows\WorkflowsCreateFromTemplateEndpoint;
use MailPoet\Automation\Engine\Endpoints\Workflows\WorkflowsGetEndpoint;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Automation\Integrations\Core\CoreIntegration;

class Engine {
  /** @var API */
  private $api;

  /** @var CoreIntegration */
  private $coreIntegration;

  /** @var Registry */
  private $registry;

  /** @var StepRunner */
  private $stepRunner;

  /** @var TriggerHandler */
  private $triggerHandler;

  /** @var WordPress */
  private $wordPress;

  /** @var WorkflowStorage */
  private $workflowStorage;

  public function __construct(
    API $api,
    CoreIntegration $coreIntegration,
    Registry $registry,
    StepRunner $stepRunner,
    TriggerHandler $triggerHandler,
    WordPress $wordPress,
    WorkflowStorage $workflowStorage
  ) {
    $this->api = $api;
    $this->coreIntegration = $coreIntegration;
    $this->registry = $registry;
    $this->stepRunner = $stepRunner;
    $this->triggerHandler = $triggerHandler;
    $this->wordPress = $wordPress;
    $this->workflowStorage = $workflowStorage;
  }

  public function initialize(): void {
    // register Action Scheduler (when behind feature flag, do it only on initialization)
    require_once __DIR__ . '/../../../vendor/woocommerce/action-scheduler/action-scheduler.php';

    $this->registerApiRoutes();

    $this->api->initialize();
    $this->stepRunner->initialize();
    $this->triggerHandler->initialize();

    $this->coreIntegration->register($this->registry);
    $this->wordPress->doAction(Hooks::INITIALIZE, [$this->registry]);
    $this->registerActiveTriggerHooks();
  }

  private function registerApiRoutes(): void {
    $this->wordPress->addAction(Hooks::API_INITIALIZE, function (API $api) {
      $api->registerGetRoute('workflows', WorkflowsGetEndpoint::class);
      $api->registerPostRoute('workflows/create-from-template', WorkflowsCreateFromTemplateEndpoint::class);
      $api->registerPostRoute('system/database', DatabasePostEndpoint::class);
      $api->registerDeleteRoute('system/database', DatabaseDeleteEndpoint::class);
    });
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
