<?php declare(strict_types = 1);

namespace MailPoet\REST\Automation\Workflows;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\REST\Automation\AutomationTest;

require_once __DIR__ . '/../AutomationTest.php';

class WorkflowsDeleteEndpointTest extends AutomationTest {
  private const ENDPOINT_PATH = '/mailpoet/v1/automation/workflows/%d';

  /** @var WorkflowStorage */
  private $workflowStorage;

  /** @var Workflow */
  private $workflow;

  public function _before() {
    parent::_before();
    $this->workflowStorage = $this->diContainer->get(WorkflowStorage::class);
    $id = $this->workflowStorage->createWorkflow(
      new Workflow(
        'Testing workflow',
        ['root' => new Step('root', Step::TYPE_ROOT, 'core:root', [], [])],
        wp_get_current_user()
      )
    );
    $workflow = $this->workflowStorage->getWorkflow($id);
    $this->assertInstanceOf(Workflow::class, $workflow);
    $this->workflow = $workflow;
  }

  public function testGuestNotAllowed(): void {
    wp_set_current_user(0);
    $data = $this->delete(sprintf(self::ENDPOINT_PATH, $this->workflow->getId()));

    $this->assertSame([
      'code' => 'rest_forbidden',
      'message' => 'Sorry, you are not allowed to do that.',
      'data' => ['status' => 401],
    ], $data);

    $workflow = $this->workflowStorage->getWorkflow($this->workflow->getId());
    $this->assertInstanceOf(Workflow::class, $workflow);
    $this->assertSame('Testing workflow', $workflow->getName());
  }

  public function testCantDeleteWorkflowWhenNotTrashed(): void {
    $data = $this->delete(sprintf(self::ENDPOINT_PATH, $this->workflow->getId()));

    $this->assertSame([
      'code' => 'mailpoet_automation_workflow_not_trashed',
      'message' => "Can't delete automation with ID '{$this->workflow->getId()}' because it was not trashed.",
      'data' => ['status' => 400, 'errors' => []],
    ], $data);

    $workflow = $this->workflowStorage->getWorkflow($this->workflow->getId());
    $this->assertInstanceOf(Workflow::class, $workflow);
    $this->assertSame('Testing workflow', $workflow->getName());
  }

  public function testItDeletesAWorkflow(): void {
    $this->workflow->setStatus(Workflow::STATUS_TRASH);
    $this->workflowStorage->updateWorkflow($this->workflow);

    $data = $this->delete(sprintf(self::ENDPOINT_PATH, $this->workflow->getId()));
    $this->assertSame(['data' => null], $data);

    $workflow = $this->workflowStorage->getWorkflow($this->workflow->getId());
    $this->assertNull($workflow);
  }

  public function _after() {
    $this->workflowStorage->truncate();
    parent::_after();
  }
}
