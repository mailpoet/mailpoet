<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNode;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNodeVisitor;

class ConsistentStepMapRule implements WorkflowNodeVisitor {
  public function initialize(Workflow $workflow): void {
    foreach ($workflow->getSteps() as $id => $step) {
      if ($id !== $step->getId()) {
        throw Exceptions::workflowStructureNotValid(__('TODO', 'mailpoet'));
      }
    }
  }

  public function visitNode(Workflow $workflow, WorkflowNode $node): void {
  }

  public function complete(Workflow $workflow): void {
  }
}
