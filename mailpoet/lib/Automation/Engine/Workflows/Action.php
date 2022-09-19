<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Workflows;

use MailPoet\Automation\Engine\Data\Step as StepData;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\Workflow;

interface Action extends Step {
  public function isValid(array $subjects, StepData $step, Workflow $workflow): bool;

  public function run(StepRunArgs $args): void;
}
