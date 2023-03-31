<?php declare(strict_types = 1);

namespace MailPoet\REST\Automation\Automations;

require_once __DIR__ . '/../AutomationTest.php';

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\REST\Automation\AutomationTest;

class AutomationsGetTest extends AutomationTest {
  private const ENDPOINT_PATH = '/mailpoet/v1/automations';

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var int[] */
  private $userIds = [];

  public function _before() {
    parent::_before();
    $this->automationStorage = $this->diContainer->get(AutomationStorage::class);
    $userId = wp_insert_user([
      'display_name' => 'test',
      'roles' => ['editor'],
      'email' => 'test@mailpoet.com',
      'user_pass' => 'abc',
      'user_login' => 'automations-get-endpoint-test',
    ]);
    $this->assertIsNumeric($userId);
    $this->userIds[] = $userId;
  }

  public function testEditorIsAllowed(): void {
    wp_set_current_user($this->editorUserId);
    $data = $this->get(self::ENDPOINT_PATH);

    $this->assertCount(0, $data['data']);
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

    $automation1Data = [
      'name' => 'Test 1',
      'status' => Automation::STATUS_DRAFT,
      'author' => 1,
    ];
    $automation2Data = [
      'name' => 'Test 2',
      'status' => Automation::STATUS_ACTIVE,
      'author' => (int)current($this->userIds),
    ];


    $expectedAutomation1Data = $automation1Data;
    $expectedAutomation2Data = $automation2Data;
    $expectedAutomation1Data['id'] = $this->createNewAutomation($automation1Data);
    $expectedAutomation2Data['id'] = $this->createNewAutomation($automation2Data);
    $expectedAutomation1Data['author'] = [
      'id' => $automation1Data['author'],
      'name' => (new \WP_User($automation1Data['author']))->display_name,
    ];
    $expectedAutomation2Data['author'] = [
      'id' => $automation2Data['author'],
      'name' => (new \WP_User($automation2Data['author']))->display_name,
    ];

    $result = $this->get(self::ENDPOINT_PATH, []);
    $this->assertIsArray($result['data']);
    $this->assertCount(2, $result['data']);
    $this->assertAutomationRestData($expectedAutomation1Data, $result['data'][1]);
    $this->assertAutomationRestData($expectedAutomation2Data, $result['data'][0]);
  }

  public function testStatusFilterWorks() {

    foreach (Automation::STATUS_ALL as $status) {
      $automation = [
        'name' => $status,
        'status' => $status,
      ];
      $this->createNewAutomation($automation);
    }

    foreach (Automation::STATUS_ALL as $status) {
      $result = $this->get(self::ENDPOINT_PATH, ['query' => ['status' => $status]]);
      $this->assertCount(1, $result['data']);
      $this->assertEquals($status, $result['data'][0]['name']);
      $this->assertEquals($status, $result['data'][0]['status']);
    }
  }

  /**
   * This small helper method can quickly assert strings or integers from
   * the REST API.
   */
  private function assertAutomationRestData($expectation, $data) {
    // We do not expect steps.
    $this->assertFalse(isset($data['steps']));
    unset($expectation['steps']);

    foreach ($expectation as $key => $expectedValue) {
      $this->assertEquals($expectedValue, $data[$key], "Failed asserting that the property $key is equal.");
    }

    // Check activated_at behavior
    if ($data['activated_at'] === null) {
      $this->assertEquals(Automation::STATUS_DRAFT, $data['status']);
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

  private function createNewAutomation(array $data = []): int {
    $rootStep = ['id' => 'root','type' => Step::TYPE_ROOT,'key' => 'core:root'];
    $data['name'] = $data['name'] ?? 'Test';
    $data['steps'] = $data['steps'] ?? [$rootStep];
    $data['author'] = $data['author'] ?? wp_get_current_user()->ID;
    $automation = new Automation(
      $data['name'],
      array_map([$this,'createStep'], $data['steps']),
      new \WP_User((int)$data['author'])
    );
    $automation->setStatus($data['status'] ?? Automation::STATUS_ACTIVE);
    return $this->automationStorage->createAutomation($automation);
  }

  private function createStep(array $data = []): Step {
    $data['id'] = $data['id'] ?? uniqid();
    $data['type'] = $data['type'] ?? Step::TYPE_ACTION;
    $data['key'] = $data['key'] ?? 'key';
    $data['args'] = $data['args'] ?? [];
    $data['nextSteps'] = $data['nextSteps'] ?? [];
    return new Step(
      $data['id'],
      $data['type'],
      $data['key'],
      $data['args'],
      $data['nextSteps']
    );
  }

  public function _after() {
    parent::_after();
    $this->automationStorage->truncate();
    foreach ($this->userIds as $userId) {
      is_multisite() ? wpmu_delete_user($userId) : wp_delete_user($userId);
    }
  }
}
