<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Workflows;

use MailPoet\Automation\Engine\Data\Step as StepData;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Data\WorkflowRun;

interface Action extends Step {
  public function isValid(array $subjects, StepData $step, Workflow $workflow): bool;

  public function run(Workflow $workflow, WorkflowRun $workflowRun, StepData $step): void;
}
