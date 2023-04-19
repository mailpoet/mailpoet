<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Integration;

use MailPoet\Automation\Engine\Data\Filter as FilterData;
use MailPoet\Validator\Schema\ObjectSchema;

interface Filter {
  public function getFieldType(): string;

  /** @return array<string, string> */
  public function getConditions(): array;

  public function getArgsSchema(): ObjectSchema;

  /** @param mixed $value */
  public function matches(FilterData $data, $value): bool;
}
