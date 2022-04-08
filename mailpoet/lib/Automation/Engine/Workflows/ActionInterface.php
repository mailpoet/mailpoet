<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Workflows;

interface ActionInterface {
  public function getKey(): string;

  public function getName(): string;

  public function validate(Workflow $workflow, Step $step, array $subjects = []): AbstractValidationResult;

  public function run(Workflow $workflow, WorkflowRun $workflowRun, Step $step): void;
}
