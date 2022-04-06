<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\Core\Actions;

use MailPoet\Automation\Engine\Control\ActionScheduler;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Workflows\Action;
use MailPoet\Automation\Engine\Workflows\ActionValidationResult;
use MailPoet\Automation\Engine\Workflows\Step;
use MailPoet\Automation\Engine\Workflows\Workflow;
use MailPoet\Automation\Engine\Workflows\WorkflowRun;
use MailPoet\InvalidStateException;

class WaitAction implements Action {
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

  public function run(ActionValidationResult $result): void {
    $this->actionScheduler->schedule($result->getValidatedParam('nextRunTime'), Hooks::WORKFLOW_STEP, [
      [
        'workflow_run_id' => $result->getValidatedParam('currentRunId'),
        'step_id' => $result->getValidatedParam('nextStepId'),
      ],
    ]);

    // TODO: call a step complete ($id) hook instead?
  }

  public function validate(Workflow $workflow, WorkflowRun $workflowRun, Step $step): ActionValidationResult {
    $result = new ActionValidationResult();
    if (!isset($step->getArgs()['seconds'])) {
      $result->addError(InvalidStateException::create());
    }
    if (!is_string($step->getNextStepId())) {
      $result->addError(InvalidStateException::create()->withMessage('next step id is required'));
    }
    if ($result->hasErrors()) {
      return $result;
    }
    $result->setValidatedParam('nextRunTime', time() + (int)$step->getArgs()['seconds'] );
    $result->setValidatedParam('currentRunId', $workflowRun->getId());
    $result->setValidatedParam('nextStepId', $step->getNextStepId());

    return $result;
  }
}
