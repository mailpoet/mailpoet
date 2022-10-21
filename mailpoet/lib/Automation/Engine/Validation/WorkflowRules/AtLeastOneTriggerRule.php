<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNode;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNodeVisitor;

class AtLeastOneTriggerRule implements WorkflowNodeVisitor {
  public const RULE_ID = 'at-least-one-trigger';

  public function initialize(Workflow $workflow): void {
    foreach ($workflow->getSteps() as $step) {
      if ($step->getType() === 'trigger') {
        return;
      }
    }

    throw Exceptions::workflowStructureNotValid(__('There must be at least one trigger in the workflow.', 'mailpoet'), self::RULE_ID);
  }

  public function visitNode(Workflow $workflow, WorkflowNode $node): void {
  }

  public function complete(Workflow $workflow): void {
  }
}
