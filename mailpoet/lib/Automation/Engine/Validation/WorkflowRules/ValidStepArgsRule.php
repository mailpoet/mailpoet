<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNode;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNodeVisitor;
use MailPoet\Validator\Validator;

class ValidStepArgsRule implements WorkflowNodeVisitor {
  /** @var Registry */
  private $registry;

  /** @var Validator */
  private $validator;

  public function __construct(
    Registry $registry,
    Validator $validator
  ) {
    $this->registry = $registry;
    $this->validator = $validator;
  }

  public function initialize(Workflow $workflow): void {
  }

  public function visitNode(Workflow $workflow, WorkflowNode $node): void {
    $step = $node->getStep();
    $registryStep = $this->registry->getStep($step->getKey());
    if (!$registryStep) {
      return;
    }

    // validate args schema only for active workflows
    if ($workflow->getStatus() === Workflow::STATUS_ACTIVE) {
      $this->validator->validate($registryStep->getArgsSchema(), $step->getArgs());
    }
  }

  public function complete(Workflow $workflow): void {
  }
}
