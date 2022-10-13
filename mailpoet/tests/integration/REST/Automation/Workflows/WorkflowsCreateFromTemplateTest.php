<?php declare(strict_types = 1);

namespace MailPoet\REST\Automation\Workflows;

require_once __DIR__ . '/../AutomationTest.php';

use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\DI\ContainerWrapper;
use MailPoet\REST\Automation\AutomationTest;

class WorkflowsCreateFromTemplateTest extends AutomationTest {
  private const ENDPOINT_PATH = '/mailpoet/v1/automation/workflows/create-from-template';

  /** @var WorkflowStorage */
  private $workflowStorage;

  public function _before() {
    parent::_before();
    $this->workflowStorage = $this->diContainer->get(WorkflowStorage::class);
  }

  public function testCreateWorkflowFromTemplate(): void {
    $countBefore = count($this->workflowStorage->getWorkflows());
    $this->post(self::ENDPOINT_PATH, [
      'json' => [
        'slug' => 'simple-welcome-email'
      ],
    ]);
    $countAfter = count($this->workflowStorage->getWorkflows());
    expect($countAfter)->equals($countBefore + 1);
  }

  public function testGuestNotAllowed(): void {
    wp_set_current_user(0);
    $countBefore = count($this->workflowStorage->getWorkflows());
    $data = $this->post(self::ENDPOINT_PATH, [
      'json' => [
        'slug' => 'simple-welcome-email'
      ],
    ]);
    $countAfter = count($this->workflowStorage->getWorkflows());
    $this->assertEquals($countBefore, $countAfter);

    $this->assertSame([
      'code' => 'rest_forbidden',
      'message' => 'Sorry, you are not allowed to do that.',
      'data' => ['status' => 401],
    ], $data);
  }

  public function testWorkflowsCreatedFromTemplatesAreCreatedInDraftStatus(): void {
    $storage = ContainerWrapper::getInstance()->get(WorkflowStorage::class);
    $this->post(self::ENDPOINT_PATH, [
      'json' => [
        'slug' => 'simple-welcome-email'
      ],
    ]);
    $allWorkflows = $storage->getWorkflows();
    $createdWorkflow = array_pop($allWorkflows);
    expect($createdWorkflow->getStatus())->equals('draft');
  }

  public function testWorkflowsCreatedFromTemplatesReturnsWorkflowId(): void {
    $storage = ContainerWrapper::getInstance()->get(WorkflowStorage::class);
    $response = $this->post(self::ENDPOINT_PATH, [
      'json' => [
        'slug' => 'simple-welcome-email'
      ],
    ]);
    $allWorkflows = $storage->getWorkflows();
    $createdWorkflow = array_pop($allWorkflows);
    $this->assertSame($createdWorkflow->getId(), $response['data']['id']);
  }

  public function _after() {
    $this->workflowStorage->truncate();
    parent::_after();
  }

}
