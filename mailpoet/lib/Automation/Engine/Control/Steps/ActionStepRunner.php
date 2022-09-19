<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Control\Steps;

use MailPoet\Automation\Engine\Control\StepRunner;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Exceptions\InvalidStateException;
use MailPoet\Automation\Engine\Registry;

class ActionStepRunner implements StepRunner {
  /** @var Registry */
  private $registry;

  public function __construct(
    Registry $registry
  ) {
    $this->registry = $registry;
  }

  public function run(StepRunArgs $args): void {
    $action = $this->registry->getAction($args->getStep()->getKey());
    if (!$action) {
      throw new InvalidStateException();
    }
    if (!$action->isValid($args->getWorkflowRun()->getSubjects(), $args->getStep(), $args->getWorkflow())) {
      throw new InvalidStateException();
    }
    $action->run($args);
  }
}
