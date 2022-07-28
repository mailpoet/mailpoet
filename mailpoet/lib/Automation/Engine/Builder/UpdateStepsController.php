<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Builder;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Registry;

class UpdateStepsController {
  /** @var Hooks */
  private $hooks;

  /** @var Registry */
  private $registry;

  public function __construct(
    Hooks $hooks,
    Registry $registry
  ) {
    $this->hooks = $hooks;
    $this->registry = $registry;
  }

  public function updateSteps(Workflow $workflow, array $data): Workflow {
    $steps = [];
    foreach ($data as $stepData) {
      $step = $this->processStep($stepData);
      $steps[$step->getId()] = $step;
    }
    $workflow->setSteps($steps);
    return $workflow;
  }

  private function processStep(array $data): Step {
    $key = $data['key'];
    $step = $this->registry->getStep($key);
    if (!$step) {
      throw Exceptions::workflowStepNotFound($key);
    }

    $stepData = new Step(
      $data['id'],
      $data['type'],
      $data['key'],
      $data['next_step_id'] ?? null,
      $data['args'] ?? null
    );

    $this->hooks->doWorkflowStepBeforeSave($stepData);
    return $stepData;
  }
}
