<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNode;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNodeVisitor;

class NoUnreachableStepsRule implements WorkflowNodeVisitor {
  public const RULE_ID = 'no-unreachable-steps';

  /** @var WorkflowNode[] */
  private $visitedNodes = [];

  public function initialize(Workflow $workflow): void {
    $this->visitedNodes = [];
  }

  public function visitNode(Workflow $workflow, WorkflowNode $node): void {
    $this->visitedNodes[] = $node;
  }

  public function complete(Workflow $workflow): void {
    if (count($this->visitedNodes) !== count($workflow->getSteps())) {
      throw Exceptions::workflowStructureNotValid(__('Unreachable steps found in workflow graph', 'mailpoet'), self::RULE_ID);
    }
  }
}
