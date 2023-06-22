<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\AutomationRules;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationNode;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationNodeVisitor;
use MailPoet\Validator\Validator;

class ValidStepFiltersRule implements AutomationNodeVisitor {
  /** @var Registry */
  private $registry;

  /** @var Validator */
  private $validator;

  public function __construct(
    Registry $registry,
    Validator $validator
  ) {
    $this->registry = $registry;
    $this->validator = $validator;
  }

  public function initialize(Automation $automation): void {
  }

  public function visitNode(Automation $automation, AutomationNode $node): void {
    $filters = $node->getStep()->getFilters();
    $groups = $filters ? $filters->getGroups() : [];
    foreach ($groups as $group) {
      foreach ($group->getFilters() as $filter) {
        $registryFilter = $this->registry->getFilter($filter->getFieldType());
        if (!$registryFilter) {
          continue;
        }
        $this->validator->validate($registryFilter->getArgsSchema($filter->getCondition()), $filter->getArgs());
      }
    }
  }

  public function complete(Automation $automation): void {
  }
}
