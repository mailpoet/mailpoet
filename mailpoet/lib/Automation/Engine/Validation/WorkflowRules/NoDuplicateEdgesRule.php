<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNode;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNodeVisitor;

class NoDuplicateEdgesRule implements WorkflowNodeVisitor {
  public const RULE_ID = 'no-duplicate-edges';

  public function initialize(Workflow $workflow): void {
  }

  public function visitNode(Workflow $workflow, WorkflowNode $node): void {
    $visitedNextStepIdsMap = [];
    foreach ($node->getStep()->getNextSteps() as $nextStep) {
      if (isset($visitedNextStepIdsMap[$nextStep->getId()])) {
        throw Exceptions::workflowStructureNotValid(__('Duplicate next step definition found', 'mailpoet'), self::RULE_ID);
      }
      $visitedNextStepIdsMap[$nextStep->getId()] = true;
    }
  }

  public function complete(Workflow $workflow): void {
  }
}
