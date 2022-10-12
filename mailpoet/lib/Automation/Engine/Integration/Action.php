<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Integration;

use MailPoet\Automation\Engine\Data\StepRunArgs;

interface Action extends Step {
  public function run(StepRunArgs $args): void;
}
