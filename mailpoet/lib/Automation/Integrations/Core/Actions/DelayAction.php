<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\Core\Actions;

use MailPoet\Automation\Engine\Control\ActionScheduler;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Workflows\Action;
use MailPoet\Automation\Engine\Workflows\Step;
use MailPoet\Automation\Engine\Workflows\Workflow;
use MailPoet\Automation\Engine\Workflows\WorkflowRun;

class DelayAction implements Action {
  /** @var ActionScheduler */
  private $actionScheduler;

  public function __construct(
    ActionScheduler $actionScheduler
  ) {
    $this->actionScheduler = $actionScheduler;
  }

  public function getKey(): string {
    return 'core:delay';
  }

  public function getName(): string {
    return __('Delay', 'mailpoet');
  }

  public function run(Workflow $workflow, WorkflowRun $workflowRun, Step $step): void {
    $this->actionScheduler->schedule(time() + $step->getArgs()['seconds'], Hooks::WORKFLOW_STEP, [
      [
        'workflow_run_id' => $workflowRun->getId(),
        'step_id' => $step->getNextStepId(),
      ],
    ]);

    // TODO: call a step complete ($id) hook instead?
  }

  public function isValid(array $subjects, Step $step, Workflow $workflow): bool {
    return (int)($step->getArgs()['seconds'] ?? null) > 0;
  }
}
