<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\AutomationRules;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationNode;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationNodeVisitor;

class NoJoinRule implements AutomationNodeVisitor {
  public const RULE_ID = 'no-join';

  /** @var array<string, Step> */
  private $visitedSteps = [];

  public function initialize(Automation $automation): void {
    $this->visitedSteps = [];
  }

  public function visitNode(Automation $automation, AutomationNode $node): void {
    $step = $node->getStep();
    $this->visitedSteps[$step->getId()] = $step;
    foreach ($step->getNextSteps() as $nextStep) {
      $nextStepId = $nextStep->getId();
      if (isset($this->visitedSteps[$nextStepId])) {
        throw Exceptions::automationStructureNotValid(__('Path join found in automation graph', 'mailpoet'), self::RULE_ID);
      }
    }
  }

  public function complete(Automation $automation): void {
  }
}
