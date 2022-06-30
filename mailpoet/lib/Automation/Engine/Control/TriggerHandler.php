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

  /** @var SubjectLoader */
  private $subjectLoader;

  /** @var WordPress */
  private $wordPress;

  /** @var WorkflowStorage */
  private $workflowStorage;

  /** @var WorkflowRunStorage */
  private $workflowRunStorage;

  public function __construct(
    ActionScheduler $actionScheduler,
    SubjectLoader $subjectLoader,
    WordPress $wordPress,
    WorkflowStorage $workflowStorage,
    WorkflowRunStorage $workflowRunStorage
  ) {
    $this->actionScheduler = $actionScheduler;
    $this->wordPress = $wordPress;
    $this->workflowStorage = $workflowStorage;
    $this->workflowRunStorage = $workflowRunStorage;
    $this->subjectLoader = $subjectLoader;
  }

  public function initialize(): void {
    $this->wordPress->addAction(Hooks::TRIGGER, [$this, 'processTrigger'], 10, 2);
  }

  /** @param array<string, array> $subjects */
  public function processTrigger(Trigger $trigger, array $subjects): void {
    $workflows = $this->workflowStorage->getActiveWorkflowsByTrigger($trigger);
    foreach ($workflows as $workflow) {
      $step = $workflow->getTrigger($trigger->getKey());
      if (!$step) {
        throw Exceptions::workflowTriggerNotFound($workflow->getId(), $trigger->getKey());
      }

      // ensure subjects are registered and loadable
      $loadedSubjects = [];
      foreach ($subjects as $key => $args) {
        $loadedSubjects[] = $this->subjectLoader->loadSubject($key, $args);
      }

      $workflowRun = new WorkflowRun($workflow->getId(), $trigger->getKey(), $loadedSubjects);
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
