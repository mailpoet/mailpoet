<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\Core\Actions\Wait;

use MailPoet\Automation\Engine\Control\ActionScheduler;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Workflows\ActionInterface;
use MailPoet\Automation\Engine\Workflows\Step;
use MailPoet\Automation\Engine\Workflows\Workflow;
use MailPoet\Automation\Engine\Workflows\WorkflowRun;
use MailPoet\InvalidStateException;

class Action implements ActionInterface {
  /** @var ActionScheduler */
  private $actionScheduler;

  public function __construct(
    ActionScheduler $actionScheduler
  ) {
    $this->actionScheduler = $actionScheduler;
  }

  public function getKey(): string {
    return 'core:wait';
  }

  public function getName(): string {
    return __('Wait', 'mailpoet');
  }

  public function run(Workflow $workflow, WorkflowRun $workflowRun, Step $step): void {
    $result = $this->validate($workflow, $step);
    if (!$result->isValid()) {
      throw InvalidStateException::create()->withErrors($result->getErrors());
    }
    $this->actionScheduler->schedule(time() + $result->getWaitTime(), Hooks::WORKFLOW_STEP, [
      [
        'workflow_run_id' => $workflowRun->getId(),
        'step_id' => $step->getNextStepId(),
      ],
    ]);

    // TODO: call a step complete ($id) hook instead?
  }

  public function validate(Workflow $workflow, Step $step, array $subjects = []): ValidationResult {
    $result = new ValidationResult();
    $seconds = $step->getArgs()['seconds'] ?? null;

    if ($seconds === null) {
      $result->addError('stepMustDefineWaitTime', "Workflow step did not include 'seconds' argument.");
    } elseif ((int)$seconds < 1) {
      $result->addError('invalidWaitTime', sprintf("'%s' is not a valid value for 'seconds'.", (int)$seconds));
    }

    $result->setWaitTime((int)$seconds);

    return $result;
  }
}
