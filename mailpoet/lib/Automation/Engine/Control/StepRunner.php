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
use Throwable;

class StepRunner {
  /** @var ActionStepRunner */
  private $actionStepRunner;

  /** @var WordPress */
  private $wordPress;

  /** @var WorkflowRunStorage */
  private $workflowRunStorage;

  /** @var WorkflowStorage */
  private $workflowStorage;

  public function __construct(
    ActionStepRunner $actionStepRunner,
    WordPress $wordPress,
    WorkflowRunStorage $workflowRunStorage,
    WorkflowStorage $workflowStorage
  ) {
    $this->actionStepRunner = $actionStepRunner;
    $this->wordPress = $wordPress;
    $this->workflowRunStorage = $workflowRunStorage;
    $this->workflowStorage = $workflowStorage;
  }

  public function initialize(): void {
    $this->wordPress->addAction(Hooks::WORKFLOW_STEP, [$this, 'run']);
  }

  /** @param mixed $args */
  public function run($args): void {
    // Action Scheduler catches only Exception instances, not other errors.
    // We need to convert them to exceptions to be processed and logged.
    try {
      $this->runStep($args);
    } catch (Throwable $e) {
      if (!$e instanceof Exception) {
        throw new Exception($e->getMessage(), intval($e->getCode()), $e);
      }
      throw $e;
    }
  }

  /** @param mixed $args */
  private function runStep($args): void {
    // TODO: args validation
    if (!is_array($args)) {
      throw new InvalidStateException();
    }

    $workflowRunId = $args['workflow_run_id'];
    $stepId = $args['step_id'];

    $workflowRun = $this->workflowRunStorage->getWorkflowRun($workflowRunId);
    if (!$workflowRun) {
      throw Exceptions::workflowRunNotFound($workflowRunId);
    }

    $workflow = $this->workflowStorage->getWorkflow($workflowRun->getWorkflowId());
    if (!$workflow) {
      throw Exceptions::workflowNotFound($workflowRun->getWorkflowId());
    }

    $step = $workflow->getStep($stepId);
    if (!$step) {
      throw Exceptions::workflowStepNotFound($stepId);
    }

    $stepType = $step->getType();
    if ($stepType === Step::TYPE_ACTION) {
      $this->actionStepRunner->run($step, $workflow, $workflowRun);
    } else {
      throw new InvalidStateException();
    }

    // TODO: enqueue next step / complete workflow
  }
}
