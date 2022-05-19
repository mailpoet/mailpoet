<?php declare(strict_types = 1);

namespace MailPoet\REST\Automation\Workflows;

require_once __DIR__ . '/../AutomationTest.php';

use MailPoet\REST\Automation\AutomationTest;

class WorkflowsGetTest extends AutomationTest {
  private const ENDPOINT_PATH = '/mailpoet/v1/automation/workflows';

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
}
