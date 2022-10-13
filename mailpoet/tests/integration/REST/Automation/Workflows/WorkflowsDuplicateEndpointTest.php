<?php declare(strict_types = 1);

namespace MailPoet\REST\Automation\Workflows;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\REST\Automation\AutomationTest;
use MailPoetVendor\Monolog\DateTimeImmutable;

require_once __DIR__ . '/../AutomationTest.php';

class WorkflowsDuplicateEndpointTest extends AutomationTest {
  private const ENDPOINT_PATH = '/mailpoet/v1/automation/workflows/%d/duplicate';

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
    $data = $this->post(sprintf(self::ENDPOINT_PATH, $this->workflow->getId()));

    $this->assertSame([
      'code' => 'rest_forbidden',
      'message' => 'Sorry, you are not allowed to do that.',
      'data' => ['status' => 401],
    ], $data);

    $workflow = $this->workflowStorage->getWorkflow($this->workflow->getId());
    $this->assertInstanceOf(Workflow::class, $workflow);
    $this->assertSame('Testing workflow', $workflow->getName());
  }

  public function testItDuplicatesAWorkflow(): void {
    $data = $this->post(sprintf(self::ENDPOINT_PATH, $this->workflow->getId()));

    $id = $this->workflow->getId() + 1;
    $user = wp_get_current_user();
    $createdAt = DateTimeImmutable::createFromFormat(DateTimeImmutable::W3C, $data['data']['created_at'] ?? null);
    $updatedAt = DateTimeImmutable::createFromFormat(DateTimeImmutable::W3C, $data['data']['updated_at'] ?? null);

    $this->assertInstanceOf(DateTimeImmutable::class, $createdAt);
    $this->assertInstanceOf(DateTimeImmutable::class, $updatedAt);
    $this->assertEquals($createdAt, $updatedAt);

    $expected = [
      'id' => $id,
      'name' => 'Copy of Testing workflow',
      'status' => 'draft',
      'created_at' => $createdAt->format(DateTimeImmutable::W3C),
      'updated_at' => $updatedAt->format(DateTimeImmutable::W3C),
      'activated_at' => null,
      'author' => [
        'id' => $user->ID,
        'name' => $user->display_name
      ],
      'stats' => [
        'workflow_id' => $id,
        'totals' => [
          'entered' => 0,
          'in_progress' => 0,
          'exited' => 0,
        ],
      ],
      'steps' => [
        'root' => [
          'id' => 'root',
          'type' => 'root',
          'key' => 'core:root',
          'args' => [],
          'next_steps' => [],
        ],
      ],
    ];
    $this->assertSame(['data' => $expected], $data);

    $expectedWorkflow = Workflow::fromArray(
      array_merge($expected, ['steps' => json_encode($expected['steps']), 'version_id' => 1])
    );
    $workflow = $this->workflowStorage->getWorkflow($id);
    $this->assertInstanceOf(Workflow::class, $workflow);
    $this->assertTrue($workflow->equals($expectedWorkflow));
  }

  public function _after() {
    $this->workflowStorage->truncate();
    parent::_after();
  }
}
