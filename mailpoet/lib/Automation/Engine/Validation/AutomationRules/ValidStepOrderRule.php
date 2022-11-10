<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\AutomationRules;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationNode;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationNodeVisitor;

class ValidStepOrderRule implements AutomationNodeVisitor {
  /** @var Registry */
  private $registry;

  public function __construct(
    Registry $registry
  ) {
    $this->registry = $registry;
  }

  public function initialize(Automation $automation): void {
  }

  public function visitNode(Automation $automation, AutomationNode $node): void {
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

    $subjectKeys = $this->collectSubjectKeys($automation, $node->getParents());
    $missingSubjectKeys = array_diff($requiredSubjectKeys, $subjectKeys);
    if (count($missingSubjectKeys) > 0) {
      throw Exceptions::missingRequiredSubjects($step, $missingSubjectKeys);
    }
  }

  public function complete(Automation $automation): void {
  }

  /**
   * @param Step[] $parents
   * @return string[]
   */
  private function collectSubjectKeys(Automation $automation, array $parents): array {
    $triggers = array_filter($parents, function (Step $step) {
      return $step->getType() === Step::TYPE_TRIGGER;
    });

    $subjectKeys = [];
    foreach ($triggers as $trigger) {
      $registryTrigger = $this->registry->getTrigger($trigger->getKey());
      if (!$registryTrigger) {
        throw Exceptions::automationTriggerNotFound($automation->getId(), $trigger->getKey());
      }
      $subjectKeys = array_merge($subjectKeys, $registryTrigger->getSubjectKeys());
    }
    return array_unique($subjectKeys);
  }
}
