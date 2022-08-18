<?php declare(strict_types = 1);

namespace MailPoet\REST\Automation\Workflows;

require_once __DIR__ . '/../AutomationTest.php';

use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\DI\ContainerWrapper;
use MailPoet\REST\Automation\AutomationTest;

class WorkflowsCreateFromTemplateTest extends AutomationTest {
  private const ENDPOINT_PATH = '/mailpoet/v1/automation/workflows/create-from-template';

  public function testCreateWorkflowFromTemplate(): void {
    $storage = ContainerWrapper::getInstance()->get(WorkflowStorage::class);
    $countBefore = count($storage->getWorkflows());
    $this->post(self::ENDPOINT_PATH, [
      'json' => [
        'slug' => 'simple-welcome-email'
      ],
    ]);
    $countAfter = count($storage->getWorkflows());
    expect($countAfter)->equals($countBefore + 1);
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

}
