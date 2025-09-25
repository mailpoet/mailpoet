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
    $data = $this->get(self::ENDPOINT_PATH)['data'];

    $this->assertCount(0, $data['items']);
    $this->assertArrayHasKey('meta', $data);
    $this->assertArrayHasKey('count', $data['meta']);
    $this->assertEquals(0, $data['meta']['count']);
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
    $this->assertSame([
      'data' => [
        'items' => [],
        'meta' => [
          'pages' => 0,
          'count' => 0,
        ],
      ],
    ], $data);
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

    $result = $this->get(self::ENDPOINT_PATH, [])['data'];
    $this->assertIsArray($result['items']);
    $this->assertCount(2, $result['items']);
    $this->assertEquals(2, $result['meta']['count']);
    // Find automations by ID to avoid ordering assumptions
    $automation1Found = false;
    $automation2Found = false;
    foreach ($result['items'] as $item) {
      if ($item['id'] === $expectedAutomation1Data['id']) {
        $this->assertAutomationRestData($expectedAutomation1Data, $item);
        $automation1Found = true;
      } elseif ($item['id'] === $expectedAutomation2Data['id']) {
        $this->assertAutomationRestData($expectedAutomation2Data, $item);
        $automation2Found = true;
      }
    }
    $this->assertTrue($automation1Found, 'Automation 1 not found in results');
    $this->assertTrue($automation2Found, 'Automation 2 not found in results');
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
      $result = $this->get(self::ENDPOINT_PATH, ['query' => ['status' => $status]])['data'];
      $this->assertCount(1, $result['items']);
      $this->assertEquals(1, $result['meta']['count']);
      $this->assertEquals($status, $result['items'][0]['name']);
      $this->assertEquals($status, $result['items'][0]['status']);
    }
  }

  public function testSearchParameterWorks(): void {
    $this->createNewAutomation(['name' => 'Welcome Email Sequence']);
    $this->createNewAutomation(['name' => 'Purchase Follow-up']);
    $this->createNewAutomation(['name' => 'Welcome SMS Campaign']);

    // Test search for "Welcome"
    $result = $this->get(self::ENDPOINT_PATH, ['query' => ['search' => 'Welcome']])['data'];
    $this->assertCount(2, $result['items']);
    $this->assertEquals(2, $result['meta']['count']);

    // Test search for "Email"
    $result = $this->get(self::ENDPOINT_PATH, ['query' => ['search' => 'Email']])['data'];
    $this->assertCount(1, $result['items']);
    $this->assertEquals(1, $result['meta']['count']);
    $this->assertEquals('Welcome Email Sequence', $result['items'][0]['name']);

    // Test search for non-existent term
    $result = $this->get(self::ENDPOINT_PATH, ['query' => ['search' => 'NonExistent']])['data'];
    $this->assertCount(0, $result['items']);
    $this->assertEquals(0, $result['meta']['count']);
  }

  public function testOrderingParametersWork(): void {
    $this->createNewAutomation(['name' => 'Alpha']);
    $this->createNewAutomation(['name' => 'Beta']);
    $this->createNewAutomation(['name' => 'Gamma']);

    // Test order by name ASC
    $result = $this->get(self::ENDPOINT_PATH, ['query' => ['orderby' => 'name', 'order' => 'ASC']])['data'];
    $this->assertCount(3, $result['items']);
    $this->assertEquals('Alpha', $result['items'][0]['name']);
    $this->assertEquals('Beta', $result['items'][1]['name']);
    $this->assertEquals('Gamma', $result['items'][2]['name']);

    // Test order by name DESC
    $result = $this->get(self::ENDPOINT_PATH, ['query' => ['orderby' => 'name', 'order' => 'DESC']])['data'];
    $this->assertCount(3, $result['items']);
    $this->assertEquals('Gamma', $result['items'][0]['name']);
    $this->assertEquals('Beta', $result['items'][1]['name']);
    $this->assertEquals('Alpha', $result['items'][2]['name']);
  }

  public function testPaginationParametersWork(): void {
    // Create 5 automations
    for ($i = 1; $i <= 5; $i++) {
      $this->createNewAutomation(['name' => "Automation $i"]);
    }

    // Test first page with 2 per page
    $result = $this->get(self::ENDPOINT_PATH, ['query' => ['page' => 1, 'per_page' => 2]])['data'];
    $this->assertCount(2, $result['items']);
    $this->assertEquals(5, $result['meta']['count']); // Total count should still be 5
    $this->assertEquals(3, $result['meta']['pages']); // ceil(5/2) = 3 pages

    // Test second page with 2 per page
    $result = $this->get(self::ENDPOINT_PATH, ['query' => ['page' => 2, 'per_page' => 2]])['data'];
    $this->assertCount(2, $result['items']);
    $this->assertEquals(5, $result['meta']['count']);

    // Test third page with 2 per page (should have 1 item)
    $result = $this->get(self::ENDPOINT_PATH, ['query' => ['page' => 3, 'per_page' => 2]])['data'];
    $this->assertCount(1, $result['items']);
    $this->assertEquals(5, $result['meta']['count']);

    // Verify different pages return different items
    $page1 = $this->get(self::ENDPOINT_PATH, ['query' => ['page' => 1, 'per_page' => 2]])['data'];
    $page2 = $this->get(self::ENDPOINT_PATH, ['query' => ['page' => 2, 'per_page' => 2]])['data'];
    $this->assertNotEquals($page1['items'][0]['id'], $page2['items'][0]['id']);
  }

  public function testCombinedParametersWork(): void {
    $this->createNewAutomation(['name' => 'Active Welcome', 'status' => Automation::STATUS_ACTIVE]);
    $this->createNewAutomation(['name' => 'Draft Welcome', 'status' => Automation::STATUS_DRAFT]);
    $this->createNewAutomation(['name' => 'Active Purchase', 'status' => Automation::STATUS_ACTIVE]);

    // Test combined search + status filter
    $result = $this->get(self::ENDPOINT_PATH, [
      'query' => [
        'search' => 'Welcome',
        'status' => Automation::STATUS_ACTIVE,
      ],
    ])['data'];
    $this->assertCount(1, $result['items']);
    $this->assertEquals(1, $result['meta']['count']);
    $this->assertEquals('Active Welcome', $result['items'][0]['name']);
    $this->assertEquals(Automation::STATUS_ACTIVE, $result['items'][0]['status']);
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
    $rootStep = ['id' => 'root', 'type' => Step::TYPE_ROOT, 'key' => 'core:root'];
    $data['name'] = $data['name'] ?? 'Test';
    $data['steps'] = $data['steps'] ?? [$rootStep];
    $data['author'] = $data['author'] ?? wp_get_current_user()->ID;
    $automation = new Automation(
      $data['name'],
      array_map([$this, 'createStep'], $data['steps']),
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
    foreach ($this->userIds as $userId) {
      is_multisite() ? wpmu_delete_user($userId) : wp_delete_user($userId);
    }
  }
}
