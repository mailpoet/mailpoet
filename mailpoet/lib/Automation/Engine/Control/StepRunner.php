<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Control;

use MailPoet\Automation\Engine\Data\StepRunArgs;

interface StepRunner {
  public function run(StepRunArgs $args): void;
}
