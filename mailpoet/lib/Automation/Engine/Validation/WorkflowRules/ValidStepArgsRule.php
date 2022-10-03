<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNode;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNodeVisitor;
use MailPoet\Validator\Validator;

class ValidStepArgsRule implements WorkflowNodeVisitor {
  /** @var Registry */
  private $registry;

  /** @var Validator */
  private $validator;

  /** @var array<string, array{step_id: string, message: string}> */
  private $errors = [];

  public function __construct(
    Registry $registry,
    Validator $validator
  ) {
    $this->registry = $registry;
    $this->validator = $validator;
  }

  public function initialize(Workflow $workflow): void {
    $this->errors = [];
  }

  public function visitNode(Workflow $workflow, WorkflowNode $node): void {
    $step = $node->getStep();
    $registryStep = $this->registry->getStep($step->getKey());
    if (!$registryStep) {
      return;
    }

    // validate args schema only for active workflows
    if ($workflow->getStatus() !== Workflow::STATUS_ACTIVE) {
      return;
    }

    try {
      $this->validator->validate($registryStep->getArgsSchema(), $step->getArgs());
    } catch (Throwable $e) {
      $this->errors[$step->getId()] = [
        'step_id' => $step->getId(),
        'message' => $e instanceof ValidationException
          ? $e->getWpError()->get_error_message()
          : __('Unknown error.', 'mailpoet'),
      ];
    }
  }

  public function complete(Workflow $workflow): void {
    if ($this->errors) {
      throw Exceptions::workflowNotValid(__('Some steps have invalid arguments', 'mailpoet'), $this->errors);
    }
  }
}
