<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Control;

use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Data\WorkflowRun;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Integration\Trigger;
use MailPoet\Automation\Engine\Storage\WorkflowRunStorage;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Automation\Engine\WordPress;

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

  /** @param Subject[] $subjects */
  public function processTrigger(Trigger $trigger, array $subjects): void {
    $workflows = $this->workflowStorage->getActiveWorkflowsByTrigger($trigger);
    foreach ($workflows as $workflow) {
      $step = $workflow->getTrigger($trigger->getKey());
      if (!$step) {
        throw Exceptions::workflowTriggerNotFound($workflow->getId(), $trigger->getKey());
      }

      // ensure subjects are registered and loadable
      $subjectEntries = $this->subjectLoader->getSubjectsEntries($subjects);
      foreach ($subjectEntries as $entry) {
        $entry->getPayload();
      }

      $workflowRun = new WorkflowRun($workflow->getId(), $workflow->getVersionId(), $trigger->getKey(), $subjects);
      if (!$trigger->isTriggeredBy(new StepRunArgs($workflow, $workflowRun, $step, $subjectEntries))) {
        return;
      }

      $workflowRunId = $this->workflowRunStorage->createWorkflowRun($workflowRun);
      $nextStep = $step->getNextSteps()[0] ?? null;
      $this->actionScheduler->enqueue(Hooks::WORKFLOW_STEP, [
        [
          'workflow_run_id' => $workflowRunId,
          'step_id' => $nextStep ? $nextStep->getId() : null,
        ],
      ]);
    }
  }
}
