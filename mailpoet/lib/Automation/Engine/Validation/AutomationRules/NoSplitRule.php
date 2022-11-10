<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\AutomationRules;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationNode;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationNodeVisitor;

class NoSplitRule implements AutomationNodeVisitor {
  public const RULE_ID = 'no-split';

  public function initialize(Automation $automation): void {
  }

  public function visitNode(Automation $automation, AutomationNode $node): void {
    $step = $node->getStep();
    if (count($step->getNextSteps()) > 1) {
      throw Exceptions::automationStructureNotValid(__('Path split found in automation graph', 'mailpoet'), self::RULE_ID);
    }
  }

  public function complete(Automation $automation): void {
  }
}
