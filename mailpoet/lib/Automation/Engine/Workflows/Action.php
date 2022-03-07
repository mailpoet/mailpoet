<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Workflows;

interface Action {
  public function getKey(): string;

  public function getName(): string;

  public function run(Workflow $workflow, WorkflowRun $workflowRun, Step $step): void;
}
