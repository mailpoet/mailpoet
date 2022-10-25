<?php

namespace MailPoet\Test\Automation\Engine\Control;

use MailPoet\Automation\Engine\Control\StepHandler;
use MailPoet\Automation\Engine\Control\StepRunner;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Data\WorkflowRun;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Exceptions\InvalidStateException;
use MailPoet\Automation\Engine\Exceptions\NotFoundException;
use MailPoet\Automation\Engine\Storage\WorkflowRunLogStorage;
use MailPoet\Automation\Engine\Storage\WorkflowRunStorage;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Automation\Integrations\Core\Actions\DelayAction;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SomeoneSubscribesTrigger;

class StepHandlerTest extends \MailPoetTest
{
  /** @var WorkflowStorage */
  private $workflowStorage;

  /** @var WorkflowRunStorage */
  private $workflowRunStorage;

  /** @var WorkflowRunLogStorage */
  private $workflowRunLogStorage;

  /** @var StepHandler */
  private $testee;

  /** @var array<string, StepRunner> */
  private $originalRunners = [];

  public function _before() {
    $this->testee = $this->diContainer->get(StepHandler::class);
    $this->workflowStorage = $this->diContainer->get(WorkflowStorage::class);
    $this->workflowRunStorage = $this->diContainer->get(WorkflowRunStorage::class);
    $this->workflowRunLogStorage = $this->diContainer->get(WorkflowRunLogStorage::class);
    $this->originalRunners = $this->testee->getStepRunners();
  }

  public function testItDoesOnlyProcessActiveAndDeactivatingWorkflows() {
    $workflow = $this->createWorkflow();
    $this->assertInstanceOf(Workflow::class, $workflow);
    $steps = $workflow->getSteps();
    $workflowRun = $this->createWorkflowRun($workflow);
    $this->assertInstanceOf(WorkflowRun::class, $workflowRun);

    $currentStep = current($steps);
    $this->assertInstanceOf(Step::class, $currentStep);
    $runner = $this->createMock(StepRunner::class);

    $runner->expects(self::exactly(2))->method('run'); // The run method will be called twice: Once for the active workflow and once for the deactivating workflow.

    $this->testee->addStepRunner($currentStep->getType(), $runner);
    $this->assertSame(Workflow::STATUS_ACTIVE, $workflow->getStatus());
    $this->testee->handle(['workflow_run_id' => $workflowRun->getId(), 'step_id' => $currentStep->getId()]);
    // no exception thrown.
    $newWorkflowRun = $this->workflowRunStorage->getWorkflowRun($workflowRun->getId());
    $this->assertInstanceOf(WorkflowRun::class, $newWorkflowRun);
    $this->assertSame(WorkflowRun::STATUS_RUNNING, $newWorkflowRun->getStatus());

    $workflow->setStatus(Workflow::STATUS_DEACTIVATING);
    $this->workflowStorage->updateWorkflow($workflow);
    $this->testee->handle(['workflow_run_id' => $workflowRun->getId(), 'step_id' => $currentStep->getId()]);
    // no exception thrown.
    $newWorkflowRun = $this->workflowRunStorage->getWorkflowRun($workflowRun->getId());
    $this->assertInstanceOf(WorkflowRun::class, $newWorkflowRun);
    $this->assertSame(WorkflowRun::STATUS_RUNNING, $newWorkflowRun->getStatus());

    $invalidStati = array_filter(
      Workflow::STATUS_ALL,
      function(string $status) : bool {
        return !in_array($status, [Workflow::STATUS_ACTIVE, Workflow::STATUS_DEACTIVATING], true);
      }
    );

    foreach ($invalidStati as $status) {
      $workflow->setStatus($status);
      $this->workflowStorage->updateWorkflow($workflow);
      $workflowRun = $this->createWorkflowRun($workflow);
      $this->assertInstanceOf(WorkflowRun::class, $workflowRun);
      $error = null;
      try {
        $this->testee->handle(['workflow_run_id' => $workflowRun->getId(), 'step_id' => $currentStep->getId()]);
      } catch (InvalidStateException $error) {
        $this->assertSame('mailpoet_automation_workflow_not_active', $error->getErrorCode(), "Workflow with '$status' did not return expected error code.");
      }
      $this->assertInstanceOf(InvalidStateException::class, $error);

      $newWorkflowRun = $this->workflowRunStorage->getWorkflowRun($workflowRun->getId());
      $this->assertInstanceOf(WorkflowRun::class, $newWorkflowRun);

      $this->assertSame(WorkflowRun::STATUS_CANCELLED, $newWorkflowRun->getStatus());
    }
  }

  public function testAnDeactivatingWorkflowGetsInactiveAfterLastRunIsExecuted() {
    $workflow = $this->createWorkflow();
    $this->assertInstanceOf(Workflow::class, $workflow);
    $workflowRun1 = $this->createWorkflowRun($workflow);
    $this->assertInstanceOf(WorkflowRun::class, $workflowRun1);
    $workflowRun2 = $this->createWorkflowRun($workflow);
    $this->assertInstanceOf(WorkflowRun::class, $workflowRun2);
    $workflow->setStatus(Workflow::STATUS_DEACTIVATING);
    $this->workflowStorage->updateWorkflow($workflow);

    $steps = $workflow->getSteps();
    $lastStep = end($steps);
    $this->assertInstanceOf(Step::class, $lastStep);
    $runner = $this->createMock(StepRunner::class);
    $this->testee->addStepRunner($lastStep->getType(), $runner);

    $this->testee->handle(['workflow_run_id' => $workflowRun1->getId(), 'step_id' => $lastStep->getId()]);
    /** @var Workflow $updatedWorkflow */
    $updatedWorkflow = $this->workflowStorage->getWorkflow($workflow->getId());
    /** @var WorkflowRun $updatedworkflowRun */
    $updatedworkflowRun = $this->workflowRunStorage->getWorkflowRun($workflowRun1->getId());
    $this->assertSame(Workflow::STATUS_DEACTIVATING, $updatedWorkflow->getStatus());
    $this->assertSame(WorkflowRun::STATUS_COMPLETE, $updatedworkflowRun->getStatus());

    $this->testee->handle(['workflow_run_id' => $workflowRun2->getId(), 'step_id' => $lastStep->getId()]);
    /** @var Workflow $updatedWorkflow */
    $updatedWorkflow = $this->workflowStorage->getWorkflow($workflow->getId());
    /** @var WorkflowRun $updatedworkflowRun */
    $updatedworkflowRun = $this->workflowRunStorage->getWorkflowRun($workflowRun1->getId());
    $this->assertSame(Workflow::STATUS_INACTIVE, $updatedWorkflow->getStatus());
    $this->assertSame(WorkflowRun::STATUS_COMPLETE, $updatedworkflowRun->getStatus());
  }

  private function createWorkflow(): ?Workflow {
    $trigger = $this->diContainer->get(SomeoneSubscribesTrigger::class);
    $delay = $this->diContainer->get(DelayAction::class);
    $steps = [
      'root' => new Step('root', Step::TYPE_ROOT, 'root', [], [new NextStep('someone-subscribes')]),
      'someone-subscribes' => new Step('someone-subscribes', Step::TYPE_TRIGGER, $trigger->getKey(), [], [new NextStep('a')]),
      'delay' => new Step('delay', Step::TYPE_ACTION, $delay->getKey(), [], []),
    ];
    $workflow = new Workflow('test', $steps, wp_get_current_user());
    $workflow->setStatus(Workflow::STATUS_ACTIVE);
    return $this->workflowStorage->getWorkflow($this->workflowStorage->createWorkflow($workflow));
  }

  private function createWorkflowRun(Workflow $workflow, $subjects = []) : ?WorkflowRun {
    $trigger = array_filter($workflow->getSteps(), function(Step $step) : bool { return $step->getType() === Step::TYPE_TRIGGER;});
    $triggerKeys = array_map(function(Step $step) : string { return $step->getKey();}, $trigger);
    $triggerKey = count($triggerKeys)>0?current($triggerKeys):'';

    $workflowRun = new WorkflowRun(
      $workflow->getId(),
      $workflow->getVersionId(),
      $triggerKey,
      $subjects
    );
    return $this->workflowRunStorage->getWorkflowRun($this->workflowRunStorage->createWorkflowRun($workflowRun));
  }

  public function _after() {
    $this->workflowStorage->truncate();
    $this->workflowRunStorage->truncate();
    $this->workflowRunLogStorage->truncate();
    $this->testee->setStepRunners($this->originalRunners);
  }

}
