<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Integration;

use MailPoet\Automation\Engine\Control\StepRunController;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepRunArgs;

interface Action extends \MailPoet\Automation\Engine\Integration\Step {
  public function run(StepRunArgs $args, StepRunController $controller): void;

  /**
   * Called when a step is duplicated to allow custom duplication logic.
   *
   * @param Step $step The step being duplicated
   * @return Step The duplicated step (may be the same instance or a modified copy)
   */
  public function onDuplicate(Step $step): Step;
}
