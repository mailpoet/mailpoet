<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNode;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNodeVisitor;

class TriggersUnderRootRule implements WorkflowNodeVisitor {
  public const RULE_ID = 'triggers-under-root';

  /** @var array<string, Step> $triggersMap */
  private $triggersMap = [];

  public function initialize(Workflow $workflow): void {
    $this->triggersMap = [];
    foreach ($workflow->getSteps() as $step) {
      if ($step->getType() === 'trigger') {
        $this->triggersMap[$step->getId()] = $step;
      }
    }
  }

  public function visitNode(Workflow $workflow, WorkflowNode $node): void {
    $step = $node->getStep();
    if ($step->getType() === Step::TYPE_ROOT) {
      return;
    }

    foreach ($step->getNextSteps() as $nextStep) {
      $nextStepId = $nextStep->getId();
      if (isset($this->triggersMap[$nextStepId])) {
        throw Exceptions::workflowStructureNotValid(__('Trigger must be a direct descendant of automation root', 'mailpoet'), self::RULE_ID);
      }
    }
  }

  public function complete(Workflow $workflow): void {
  }
}
