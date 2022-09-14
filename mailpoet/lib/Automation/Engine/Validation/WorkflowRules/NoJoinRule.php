<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNode;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNodeVisitor;

class NoJoinRule implements WorkflowNodeVisitor {
  /** @var array<string, Step> */
  private $visitedSteps = [];

  public function initialize(Workflow $workflow): void {
    $this->visitedSteps = [];
  }

  public function visitNode(Workflow $workflow, WorkflowNode $node): void {
    $step = $node->getStep();
    $this->visitedSteps[$step->getId()] = $step;
    foreach ($step->getNextSteps() as $nextStep) {
      $nextStepId = $nextStep->getId();
      if (isset($this->visitedSteps[$nextStepId])) {
        throw Exceptions::workflowStructureNotValid(__('Path join found in workflow graph', 'mailpoet'));
      }
    }
  }

  public function complete(Workflow $workflow): void {
  }
}
