<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Control;

use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Storage\WorkflowRunStorage;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\Automation\Engine\Workflows\Trigger;
use MailPoet\Automation\Engine\Workflows\WorkflowRun;

class TriggerHandler {
  /** @var ActionScheduler */
  private $actionScheduler;

  /** @var WordPress */
  private $wordPress;

  /** @var WorkflowStorage */
  private $workflowStorage;

  /** @var WorkflowRunStorage */
  private $workflowRunStorage;

  public function __construct(
    ActionScheduler $actionScheduler,
    WordPress $wordPress,
    WorkflowStorage $workflowStorage,
    WorkflowRunStorage $workflowRunStorage
  ) {
    $this->actionScheduler = $actionScheduler;
    $this->wordPress = $wordPress;
    $this->workflowStorage = $workflowStorage;
    $this->workflowRunStorage = $workflowRunStorage;
  }

  public function initialize(): void {
    $this->wordPress->addAction(Hooks::TRIGGER, [$this, 'processTrigger']);
  }

  public function processTrigger(Trigger $trigger): void {
    $workflows = $this->workflowStorage->getActiveWorkflowsByTrigger($trigger);
    foreach ($workflows as $workflow) {
      $step = $workflow->getTrigger($trigger->getKey());
      if (!$step) {
        throw Exceptions::workflowTriggerNotFound($workflow->getId(), $trigger->getKey());
      }

      $workflowRun = new WorkflowRun($workflow->getId(), $trigger->getKey());
      $workflowRunId = $this->workflowRunStorage->createWorkflowRun($workflowRun);

      $this->actionScheduler->enqueue(Hooks::WORKFLOW_STEP, [
        [
          'workflow_run_id' => $workflowRunId,
          'step_id' => $step->getNextStepId(),
        ],
      ]);
    }
  }
}
