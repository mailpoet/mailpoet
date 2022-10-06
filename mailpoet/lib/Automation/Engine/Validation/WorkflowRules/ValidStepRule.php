<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNode;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNodeVisitor;
use Throwable;

class ValidStepRule implements WorkflowNodeVisitor {
  /** @var WorkflowNodeVisitor[] */
  private $rules;

  /** @var array<string, array{step_id: string, message: string}> */
  private $errors = [];

  /** @param WorkflowNodeVisitor[] $rules */
  public function __construct(
    array $rules
  ) {
    $this->rules = $rules;
  }

  public function initialize(Workflow $workflow): void {
    // run full step validation only for active workflows
    if ($workflow->getStatus() !== Workflow::STATUS_ACTIVE) {
      return;
    }

    foreach ($this->rules as $rule) {
      $rule->initialize($workflow);
    }
  }

  public function visitNode(Workflow $workflow, WorkflowNode $node): void {
    // run full step validation only for active workflows
    if ($workflow->getStatus() !== Workflow::STATUS_ACTIVE) {
      return;
    }

    foreach ($this->rules as $rule) {
      $stepId = $node->getStep()->getId();
      try {
        $rule->visitNode($workflow, $node);
      } catch (UnexpectedValueException $e) {
        $this->errors[$stepId] = ['step_id' => $stepId, 'message' => $e->getMessage()];
      } catch (Throwable $e) {
        $this->errors[$stepId] = ['step_id' => $stepId, 'message' => __('Unknown error.', 'mailpoet')];
      }
    }
  }

  public function complete(Workflow $workflow): void {
    // run full step validation only for active workflows
    if ($workflow->getStatus() !== Workflow::STATUS_ACTIVE) {
      return;
    }

    foreach ($this->rules as $rule) {
      $rule->complete($workflow);
    }

    if ($this->errors) {
      throw Exceptions::workflowNotValid(__('Some steps are not valid', 'mailpoet'), $this->errors);
    }
  }
}
