<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Integration;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step as StepData;

interface TimeBasedTrigger extends Trigger {
  public function findItemsToTrigger(Automation $automation, StepData $triggerData, int $offset): int;

  public function getLimit(): int;
}
