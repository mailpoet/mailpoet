<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Workflows;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Data\WorkflowRun;

interface Action {
  public function getKey(): string;

  public function getName(): string;

  public function isValid(array $subjects, Step $step, Workflow $workflow): bool;

  public function run(Workflow $workflow, WorkflowRun $workflowRun, Step $step): void;
}
