<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Control;

use Exception;
use MailPoet\Automation\Engine\Control\Steps\ActionStepRunner;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Exceptions\InvalidStateException;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Storage\WorkflowRunStorage;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\Automation\Engine\Workflows\Step;
use MailPoet\Automation\Engine\Workflows\WorkflowRun;
use Throwable;

class StepHandler {
  /** @var ActionScheduler */
  private $actionScheduler;

  /** @var ActionStepRunner */
  private $actionStepRunner;

  /** @var WordPress */
  private $wordPress;

  /** @var WorkflowRunStorage */
  private $workflowRunStorage;

  /** @var WorkflowStorage */
  private $workflowStorage;

  /** @var array<string, StepRunner> */
  private $stepRunners;

  public function __construct(
    ActionScheduler $actionScheduler,
    ActionStepRunner $actionStepRunner,
    WordPress $wordPress,
    WorkflowRunStorage $workflowRunStorage,
    WorkflowStorage $workflowStorage
  ) {
    $this->actionScheduler = $actionScheduler;
    $this->actionStepRunner = $actionStepRunner;
    $this->wordPress = $wordPress;
    $this->workflowRunStorage = $workflowRunStorage;
    $this->workflowStorage = $workflowStorage;
  }

  public function initialize(): void {
    $this->wordPress->addAction(Hooks::WORKFLOW_STEP, [$this, 'handle']);
    $this->addStepRunner(Step::TYPE_ACTION, $this->actionStepRunner);
    $this->wordPress->doAction(Hooks::STEP_RUNNER_INITIALIZE, [$this]);
  }

  public function addStepRunner(string $stepType, StepRunner $stepRunner): void {
    $this->stepRunners[$stepType] = $stepRunner;
  }

  /** @param mixed $args */
  public function handle($args): void {
    // TODO: args validation
    if (!is_array($args)) {
      throw new InvalidStateException();
    }

    // Action Scheduler catches only Exception instances, not other errors.
    // We need to convert them to exceptions to be processed and logged.
    try {
      $this->handleStep($args);
    } catch (Throwable $e) {
      $this->workflowRunStorage->updateStatus((int)$args['workflow_run_id'], WorkflowRun::STATUS_FAILED);
      if (!$e instanceof Exception) {
        throw new Exception($e->getMessage(), intval($e->getCode()), $e);
      }
      throw $e;
    }
  }

  private function handleStep(array $args): void {
    $workflowRunId = $args['workflow_run_id'];
    $stepId = $args['step_id'];

    $workflowRun = $this->workflowRunStorage->getWorkflowRun($workflowRunId);
    if (!$workflowRun) {
      throw Exceptions::workflowRunNotFound($workflowRunId);
    }

    if ($workflowRun->getStatus() !== WorkflowRun::STATUS_RUNNING) {
      throw Exceptions::workflowRunNotRunning($workflowRunId, $workflowRun->getStatus());
    }

    $workflow = $this->workflowStorage->getWorkflow($workflowRun->getWorkflowId());
    if (!$workflow) {
      throw Exceptions::workflowNotFound($workflowRun->getWorkflowId());
    }

    // complete workflow run
    if (!$stepId) {
      $this->workflowRunStorage->updateStatus($workflowRunId, WorkflowRun::STATUS_COMPLETE);
      return;
    }

    $step = $workflow->getStep($stepId);
    if (!$step) {
      throw Exceptions::workflowStepNotFound($stepId);
    }

    $stepType = $step->getType();
    if (isset($this->stepRunners[$stepType])) {
      $this->stepRunners[$stepType]->run($step, $workflow, $workflowRun);
    } else {
      throw new InvalidStateException();
    }

    $nextStepArgs = [
      [
        'workflow_run_id' => $workflowRunId,
        'step_id' => $step->getNextStepId(),
      ],
    ];

    // next step scheduled by action
    if ($this->actionScheduler->hasScheduledAction(Hooks::WORKFLOW_STEP, $nextStepArgs)) {
      return;
    }

    // no need to schedule a new step if the next step is null, complete the run
    if (empty($nextStepArgs[0]['step_id'])) {
      $this->workflowRunStorage->updateStatus($workflowRunId, WorkflowRun::STATUS_COMPLETE);
      return;
    }

    // enqueue next step
    $this->actionScheduler->enqueue(Hooks::WORKFLOW_STEP, $nextStepArgs);

    // TODO: allow long-running steps (that are not done here yet)
  }
}
