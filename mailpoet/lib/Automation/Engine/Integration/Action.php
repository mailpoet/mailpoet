<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Integration;

use MailPoet\Automation\Engine\Control\StepRunController;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepRunArgs;

interface Action extends \MailPoet\Automation\Engine\Integration\Step {
  public function run(StepRunArgs $args, StepRunController $controller): void;

  public function onDuplicate(Step $step): Step;
}
