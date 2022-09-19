<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Control\Steps;

use MailPoet\Automation\Engine\Control\StepRunner;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\SubjectEntry;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Data\WorkflowRun;
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

  /** @param SubjectEntry[] $subjectEntries */
  public function run(Step $step, Workflow $workflow, WorkflowRun $workflowRun, array $subjectEntries): void {
    $action = $this->registry->getAction($step->getKey());
    if (!$action) {
      throw new InvalidStateException();
    }
    if (!$action->isValid($workflowRun->getSubjects(), $step, $workflow)) {
      throw new InvalidStateException();
    }
    $action->run($workflow, $workflowRun, $step);
  }
}
