<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNode;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNodeVisitor;

class NoCycleRule implements WorkflowNodeVisitor {
  public const RULE_ID = 'no-cycle';

  public function initialize(Workflow $workflow): void {
  }

  public function visitNode(Workflow $workflow, WorkflowNode $node): void {
    $step = $node->getStep();
    $parents = $node->getParents();
    $parentIdsMap = array_combine(
      array_map(function (Step $parent) {
        return $parent->getId();
      }, $node->getParents()),
      $parents
    ) ?: [];

    foreach ($step->getNextSteps() as $nextStep) {
      $nextStepId = $nextStep->getId();
      if ($nextStepId === $step->getId() || isset($parentIdsMap[$nextStepId])) {
        throw Exceptions::workflowStructureNotValid(__('Cycle found in workflow graph', 'mailpoet'), self::RULE_ID);
      }
    }
  }

  public function complete(Workflow $workflow): void {
  }
}
