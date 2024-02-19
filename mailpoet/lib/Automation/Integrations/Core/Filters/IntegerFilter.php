<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\Core\Filters;

use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Engine\Data\Filter as FilterData;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;

class IntegerFilter extends NumberFilter {
  public function getFieldType(): string {
    return Field::TYPE_INTEGER;
  }

  public function getArgsSchema(string $condition): ObjectSchema {
    switch ($condition) {
      case self::CONDITION_BETWEEN:
      case self::CONDITION_NOT_BETWEEN:
        return Builder::object([
          'value' => Builder::array(Builder::integer())->minItems(2)->maxItems(2)->required(),
        ]);
      case self::CONDITION_IS_SET:
      case self::CONDITION_IS_NOT_SET:
        return Builder::object([]);
      default:
        return Builder::object(['value' => Builder::integer()->required()]);
    }
  }

  public function getFieldParams(FilterData $data): array {
    return [];
  }

  public function matches(FilterData $data, $value): bool {
    $matches = parent::matches($data, $value);
    if (!$matches) {
      return false;
    }

    if (isset($value) && !$this->isWholeNumber($value)) {
      return false;
    }

    $filterValue = $data->getArgs()['value'] ?? null;
    if (is_array($filterValue)) {
      foreach ($filterValue as $filterValueItem) {
        if (!$this->isWholeNumber($filterValueItem)) {
          return false;
        }
      }
      return true;
    }

    if (isset($filterValue) && !$this->isWholeNumber($filterValue)) {
      return false;
    }
    return true;
  }

  /** @param mixed $value */
  private function isWholeNumber($value): bool {
    return is_int($value) || (is_float($value) && $value === floor($value));
  }
}
