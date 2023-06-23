<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\AutomationRules;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationNode;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationNodeVisitor;
use MailPoet\Validator\ValidationException;
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
    $errors = [];
    foreach ($groups as $group) {
      foreach ($group->getFilters() as $filter) {
        $registryFilter = $this->registry->getFilter($filter->getFieldType());
        if (!$registryFilter) {
          continue;
        }
        try {
          $this->validator->validate($registryFilter->getArgsSchema($filter->getCondition()), $filter->getArgs());
        } catch (ValidationException $e) {
          $errors[$filter->getId()] = $e->getWpError()->get_error_code();
        }
      }
    }

    if ($errors) {
      $throwable = ValidationException::create()->withMessage('invalid-automation-filters');
      foreach ($errors as $errorKey => $errorMsg) {
        $throwable->withError((string)$errorKey, (string)$errorMsg);
      }
      throw $throwable;
    }
  }

  public function complete(Automation $automation): void {
  }
}
