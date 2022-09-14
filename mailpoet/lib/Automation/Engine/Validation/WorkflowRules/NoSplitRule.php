<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNode;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNodeVisitor;

class NoSplitRule implements WorkflowNodeVisitor {
  public function initialize(Workflow $workflow): void {
  }

  public function visitNode(Workflow $workflow, WorkflowNode $node): void {
    $step = $node->getStep();
    if (count($step->getNextSteps()) > 1) {
      throw Exceptions::workflowStructureNotValid(__('Path split found in workflow graph', 'mailpoet'));
    }
  }

  public function complete(Workflow $workflow): void {
  }
}
