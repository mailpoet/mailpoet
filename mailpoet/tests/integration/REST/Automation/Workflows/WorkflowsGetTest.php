<?php declare(strict_types = 1);

namespace MailPoet\REST\Automation\Workflows;

require_once __DIR__ . '/../AutomationTest.php';

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\REST\Automation\AutomationTest;

class WorkflowsGetTest extends AutomationTest {
  private const ENDPOINT_PATH = '/mailpoet/v1/automation/workflows';

  /** @var WorkflowStorage */
  private $workflowStorage;

  /** @var int[] */
  private $userIds = [];

  public function _before() {
    parent::_before();
    $this->workflowStorage = $this->diContainer->get(WorkflowStorage::class);
    $userId = wp_insert_user([
      'display_name' => 'test',
      'roles' => ['editor'],
      'email' => 'test@mailpoet.com',
      'user_pass' => 'abc',
      'user_login' => 'workflows-get-endpoint-test',
    ]);
    $this->assertIsNumeric($userId);
    $this->userIds[] = $userId;
  }

  public function testGuestNotAllowed(): void {
    wp_set_current_user(0);
    $data = $this->get(self::ENDPOINT_PATH);

    $this->assertSame([
      'code' => 'rest_forbidden',
      'message' => 'Sorry, you are not allowed to do that.',
      'data' => ['status' => 401],
    ], $data);
  }

  public function testRequest(): void {
    $data = $this->get(self::ENDPOINT_PATH);
    $this->assertSame(['data' => []], $data);
  }

  public function testGetAll() {

    $workflow1Data = [
      'name' => 'Test 1',
      'status' => Workflow::STATUS_DRAFT,
      'author' => 1,
    ];
    $workflow2Data = [
      'name' => 'Test 2',
      'status' => Workflow::STATUS_ACTIVE,
      'author' => (int) current($this->userIds),
    ];


    $expectedWorkflow1Data = $workflow1Data;
    $expectedWorkflow2Data = $workflow2Data;
    $expectedWorkflow1Data['id'] = $this->createNewWorkflow($workflow1Data);
    $expectedWorkflow2Data['id'] = $this->createNewWorkflow($workflow2Data);
    $expectedWorkflow1Data['author'] = [
      'id' => $workflow1Data['author'],
      'name' => (new \WP_User($workflow1Data['author']))->display_name,
    ];
    $expectedWorkflow2Data['author'] = [
      'id' => $workflow2Data['author'],
      'name' => (new \WP_User($workflow2Data['author']))->display_name,
    ];

    $result = $this->get(self::ENDPOINT_PATH, []);
    $this->assertIsArray($result['data']);
    $this->assertCount(2, $result['data']);
    $this->assertWorkflowRestData($expectedWorkflow1Data, $result['data'][1]);
    $this->assertWorkflowRestData($expectedWorkflow2Data, $result['data'][0]);
  }

  public function testStatusFilterWorks() {

    foreach (Workflow::STATUS_ALL as $status) {
      $workflow = [
        'name' => $status,
        'status' => $status,
      ];
      $this->createNewWorkflow($workflow);
    }

    foreach (Workflow::STATUS_ALL as $status) {
      $result = $this->get(self::ENDPOINT_PATH, ['query'=> ['status' => $status]]);
      $this->assertCount(1, $result['data']);
      $this->assertEquals($status, $result['data'][0]['name']);
      $this->assertEquals($status, $result['data'][0]['status']);
    }
  }

  /**
   * This small helper method can quickly assert strings or integers from
   * the REST API.
   */
  private function assertWorkflowRestData($expectation, $data) {
    // We do not expect steps.
    $this->assertFalse(isset($data['steps']));
    unset($expectation['steps']);

    foreach ($expectation as $key => $expectedValue) {
      $this->assertEquals($expectedValue, $data[$key], "Failed asserting that the property $key is equal.");
    }

    // Check activated_at behavior
    if ($data['activated_at'] === null) {
      $this->assertEquals(Workflow::STATUS_DRAFT, $data['status']);
    } else {
      $activatedAt = null;
      try {
        $activatedAt = new \DateTimeImmutable($data['activated_at']);
      } finally {
        $this->assertInstanceOf(\DateTimeImmutable::class, $activatedAt);
      }
    }

    // Check the date time fields are convertible.
    $updatedAt = null;
    $createdAt = null;
    try {
      $updatedAt = new \DateTimeImmutable($data['updated_at']);
      $createdAt = new \DateTimeImmutable($data['created_at']);
    } finally {
      $this->assertInstanceOf(\DateTimeImmutable::class, $updatedAt);
      $this->assertInstanceOf(\DateTimeImmutable::class, $createdAt);
    }
  }

  private function createNewWorkflow(array $data = []) : int {
    $rootStep = ['id'=>'root','type'=>Step::TYPE_ROOT,'key'=>'core:root'];
    $data['name'] = $data['name']??'Test';
    $data['steps'] = $data['steps']??[$rootStep];
    $data['author'] = $data['author']??wp_get_current_user()->ID;
    $workflow = new Workflow(
      $data['name'],
      array_map([$this,'createStep'],$data['steps']),
      new \WP_User((int)$data['author'])
    );
    $workflow->setStatus($data['status']??Workflow::STATUS_ACTIVE);
    return $this->workflowStorage->createWorkflow($workflow);
  }

  private function createStep(array $data = []) : Step {
    $data['id'] = $data['id']??uniqid();
    $data['type'] = $data['type']??Step::TYPE_ACTION;
    $data['key'] = $data['key']??'key';
    $data['args'] = $data['args']??[];
    $data['nextSteps']=$data['nextSteps']??[];
    return new Step(
      $data['id'],
      $data['type'],
      $data['key'],
      $data['args'],
      $data['nextSteps']
    );
  }

  public function _after() {
    $this->workflowStorage->truncate();
    foreach ($this->userIds as $userId) {
      is_multisite() ? wpmu_delete_user($userId) : wp_delete_user($userId);
    }
    parent::_after();
  }
}
