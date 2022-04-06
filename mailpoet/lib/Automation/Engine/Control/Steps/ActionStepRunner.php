<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Control\Steps;

use MailPoet\Automation\Engine\Exceptions\InvalidStateException;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Workflows\Step;
use MailPoet\Automation\Engine\Workflows\Workflow;
use MailPoet\Automation\Engine\Workflows\WorkflowRun;

class ActionStepRunner {
  /** @var Registry */
  private $registry;

  public function __construct(
    Registry $registry
  ) {
    $this->registry = $registry;
  }

  public function run(Step $step, Workflow $workflow, WorkflowRun $workflowRun): void {
    $action = $this->registry->getAction($step->getKey());
    if (!$action) {
      throw new InvalidStateException();
    }
    $validationResult = $action->validate($workflow, $workflowRun, $step);
    if (!$validationResult->hasErrors()) {
      $action->run($validationResult);
    }
  }
}
