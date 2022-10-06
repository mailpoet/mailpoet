<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNode;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNodeVisitor;

class ConsistentStepMapRule implements WorkflowNodeVisitor {
  public const RULE_ID = 'consistent-step-map';

  public function initialize(Workflow $workflow): void {
    foreach ($workflow->getSteps() as $id => $step) {
      if ($id !== $step->getId()) {
        // translators: %1$s is the ID of the step, %2$s is its index in the steps object.
        throw Exceptions::workflowStructureNotValid(
          sprintf(__("Step with ID '%1\$s' stored under a mismatched index '%2\$s'.", 'mailpoet'), $step->getId(), $id),
          self::RULE_ID
        );
      }
    }
  }

  public function visitNode(Workflow $workflow, WorkflowNode $node): void {
  }

  public function complete(Workflow $workflow): void {
  }
}
