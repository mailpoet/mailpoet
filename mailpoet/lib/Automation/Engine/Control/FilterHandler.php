<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Control;

use MailPoet\Automation\Engine\Data\Filter as FilterData;
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
    foreach ($filters as $filter) {
      $value = $args->getFieldValue($filter->getFieldKey());
      if (!$this->matchesFilter($filter, $value)) {
        return false;
      }
    }
    return true;
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
