<?php

namespace MailPoet\REST\Automation\Workflows;

use MailPoet\Automation\Engine\Builder\CreateWorkflowFromTemplateController;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\REST\Automation\AutomationTest;

require_once __DIR__ . '/../AutomationTest.php';

class WorkflowPutEndpointTest extends AutomationTest
{
  private const ENDPOINT_PATH = '/mailpoet/v1/automation/workflows/%d';

  /** @var WorkflowStorage */
  private $workflowStorage;

  /** @var CreateWorkflowFromTemplateController */
  private $createWorkflow;

  /** @var Workflow */
  private $workflow;

  public function _before() {
    parent::_before();
    $this->workflowStorage = $this->diContainer->get(WorkflowStorage::class);
    $this->createWorkflow = $this->diContainer->get(CreateWorkflowFromTemplateController::class);
    $this->workflow = $this->createWorkflow->createWorkflow('simple-welcome-email');
    assert($this->workflow instanceof Workflow);
  }

  public function testGuestNotAllowed(): void {
    wp_set_current_user(0);
    $data = $this->put(
      sprintf(self::ENDPOINT_PATH, $this->workflow->getId()),
      [
        'json' => [
          'name' => 'Test',
        ]
      ]
    );

    $this->assertSame([
      'code' => 'rest_forbidden',
      'message' => 'Sorry, you are not allowed to do that.',
      'data' => ['status' => 401],
    ], $data);

    $workflow = $this->workflowStorage->getWorkflow($this->workflow->getId());
    assert($workflow instanceof Workflow);
    $this->assertSame('Simple welcome email', $workflow->getName());
  }

  public function testUpdateWorkflow(): void {
    $changes = [];
    $trigger = $this->workflow->getTrigger('mailpoet:someone-subscribes');
    assert($trigger instanceof Step);
    $changes[$trigger->getId()] = [
      'args' => [
        'segment_ids' => [1,2]
      ]
    ];
    $updatedSteps = $this->getChangedStepsStructureOfWorkflow($this->workflow, $changes);
    $data = $this->put(
      sprintf(self::ENDPOINT_PATH, $this->workflow->getId()),
      [
        'json' => [
          'name' => 'Test',
          'status' => Workflow::STATUS_TRASH,
          'steps' => $updatedSteps,
        ]
      ]
    );

    $updatedWorkflow = $this->workflowStorage->getWorkflow($this->workflow->getId());
    assert($updatedWorkflow instanceof Workflow);
    $updatedTrigger = $updatedWorkflow->getTrigger('mailpoet:someone-subscribes');
    assert($updatedTrigger instanceof Step);

    /** Ensure the old workflow does not already contain the values we attempt to change */
    $this->assertNotSame($changes[$trigger->getId()]['args'], $trigger->getArgs());
    $this->assertNotSame('test', $this->workflow->getName());
    $this->assertNotSame(Workflow::STATUS_TRASH, $this->workflow->getStatus());

    /** Ensure, the changes have been stored to the database */
    $this->assertSame('Test', $updatedWorkflow->getName());
    $this->assertSame(Workflow::STATUS_TRASH, $updatedWorkflow->getStatus());
    $this->assertSame($changes[$trigger->getId()]['args'], $updatedTrigger->getArgs());

    /** Ensure the updated workflow gets returned from the endpoint */
    $this->assertSame('Test', $data['data']['name']);
    $this->assertSame(Workflow::STATUS_TRASH, $data['data']['status']);
  }

  public function testWorkflowBasicValidationWorks(): void {
    $data = $this->put(
      sprintf(self::ENDPOINT_PATH, $this->workflow->getId()),
      [
        'json' => [
          'steps' => [
            'root' => [
              'id' => 'root',
              'key' => 'core:root',
              'type' => Step::TYPE_ROOT,
              'args' => [],
              'next_steps' => [],
            ],
          ],
        ]
      ]
    );

    $this->assertSame('mailpoet_automation_workflow_structure_modification_not_supported', $data['code']);
    $workflow = $this->workflowStorage->getWorkflow($this->workflow->getId());
    assert($workflow instanceof Workflow);
    /** Ensure, the changes have not been stored to the database */
    $this->assertSame($this->workflow->getVersionId(), $workflow->getVersionId());
  }

  private function getChangedStepsStructureOfWorkflow(Workflow $workflow, array $changes = []) {
    $steps = $workflow->getSteps();
    $data = [];
    foreach ($steps as $step) {
      $data[$step->getId()] = array_merge(
        $step->toArray(),
        $changes[$step->getId()]??[]
      );
    }
    return $data;
  }

  public function _after() {
    $this->workflowStorage->flush();
    parent::_after();
  }
}
