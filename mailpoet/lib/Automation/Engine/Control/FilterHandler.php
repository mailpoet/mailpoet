<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Control;

use MailPoet\Automation\Engine\Data\Filter as FilterData;
use MailPoet\Automation\Engine\Data\FilterGroup;
use MailPoet\Automation\Engine\Data\Filters;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Registry;

class FilterHandler {
  /** @var Registry */
  private $registry;

  public function __construct(
    Registry $registry
  ) {
    $this->registry = $registry;
  }

  public function matchesFilters(StepRunArgs $args): bool {
    $filters = $args->getStep()->getFilters();
    if (!$filters) {
      return true;
    }

    $operator = $filters->getOperator();
    foreach ($filters->getGroups() as $group) {
      $matches = $this->matchesGroup($group, $args);
      if ($operator === Filters::OPERATOR_AND && !$matches) {
        return false;
      }
      if ($operator === Filters::OPERATOR_OR && $matches) {
        return true;
      }
    }
    return $operator === Filters::OPERATOR_AND;
  }

  private function matchesGroup(FilterGroup $group, StepRunArgs $args): bool {
    $operator = $group->getOperator();
    foreach ($group->getFilters() as $filter) {
      $value = $args->getFieldValue($filter->getFieldKey());
      $matches = $this->matchesFilter($filter, $value);
      if ($operator === FilterGroup::OPERATOR_AND && !$matches) {
        return false;
      }
      if ($operator === FilterGroup::OPERATOR_OR && $matches) {
        return true;
      }
    }
    return $operator === FilterGroup::OPERATOR_AND;
  }

  /** @param mixed $value */
  private function matchesFilter(FilterData $data, $value): bool {
    $filter = $this->registry->getFilter($data->getFieldType());
    if (!$filter) {
      throw Exceptions::filterNotFound($data->getFieldType());
    }
    return $filter->matches($data, $value);
  }
}
