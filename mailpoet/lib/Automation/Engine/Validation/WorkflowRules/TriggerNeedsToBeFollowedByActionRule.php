<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNode;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNodeVisitor;

class TriggerNeedsToBeFollowedByActionRule implements WorkflowNodeVisitor {
  public const RULE_ID = 'trigger-needs-to-be-followed-by-action';

  public function initialize(Workflow $workflow): void {
  }

  public function visitNode(Workflow $workflow, WorkflowNode $node): void {
    // run full step validation only for active workflows
    if ($workflow->getStatus() !== Workflow::STATUS_ACTIVE) {
      return;
    }

    $step = $node->getStep();
    if ($step->getType() !== Step::TYPE_TRIGGER) {
      return;
    }
    $nextSteps = $step->getNextSteps();
    if (!count($nextSteps)) {
      throw Exceptions::workflowStructureNotValid(__('A trigger needs to be followed by an action.', 'mailpoet'), self::RULE_ID);
    }
    foreach ($nextSteps as $step) {
      $step = $workflow->getStep($step->getId());
      if ($step && $step->getType() === Step::TYPE_ACTION) {
        continue;
      }
      throw Exceptions::workflowStructureNotValid(__('A trigger needs to be followed by an action.', 'mailpoet'), self::RULE_ID);
    }
  }

  public function complete(Workflow $workflow): void {
  }
}
