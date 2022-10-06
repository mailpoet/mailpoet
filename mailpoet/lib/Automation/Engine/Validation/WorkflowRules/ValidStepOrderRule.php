<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNode;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNodeVisitor;

class ValidStepOrderRule implements WorkflowNodeVisitor {
  /** @var Registry */
  private $registry;

  public function __construct(
    Registry $registry
  ) {
    $this->registry = $registry;
  }

  public function initialize(Workflow $workflow): void {
  }

  public function visitNode(Workflow $workflow, WorkflowNode $node): void {
    $step = $node->getStep();
    $registryStep = $this->registry->getStep($step->getKey());
    if (!$registryStep) {
      return;
    }

    // triggers don't require any subjects (they provide them)
    if ($step->getType() === Step::TYPE_TRIGGER) {
      return;
    }

    $requiredSubjectKeys = $registryStep->getSubjectKeys();
    if (!$requiredSubjectKeys) {
      return;
    }

    $subjectKeys = $this->collectSubjectKeys($workflow, $node->getParents());
    $missingSubjectKeys = array_diff($requiredSubjectKeys, $subjectKeys);
    if (count($missingSubjectKeys) > 0) {
      throw Exceptions::missingRequiredSubjects($step, $missingSubjectKeys);
    }
  }

  public function complete(Workflow $workflow): void {
  }

  /**
   * @param Step[] $parents
   * @return string[]
   */
  private function collectSubjectKeys(Workflow $workflow, array $parents): array {
    $triggers = array_filter($parents, function (Step $step) {
      return $step->getType() === Step::TYPE_TRIGGER;
    });

    $subjectKeys = [];
    foreach ($triggers as $trigger) {
      $registryTrigger = $this->registry->getTrigger($trigger->getKey());
      if (!$registryTrigger) {
        throw Exceptions::workflowTriggerNotFound($workflow->getId(), $trigger->getKey());
      }
      $subjectKeys = array_merge($subjectKeys, $registryTrigger->getSubjectKeys());
    }
    return array_unique($subjectKeys);
  }
}
