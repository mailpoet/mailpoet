<?php declare(strict_types = 1);

namespace MailPoet\REST\Automation\Workflows;

require_once __DIR__ . '/../AutomationTest.php';

use MailPoet\REST\Automation\AutomationTest;

class WorkflowsPostTest extends AutomationTest {
  private const ENDPOINT_PATH = '/mailpoet/v1/automation/workflows';

  public function testGuestNotAllowed(): void {
    wp_set_current_user(0);
    $data = $this->post(self::ENDPOINT_PATH);

    $this->assertSame([
      'code' => 'rest_forbidden',
      'message' => 'Sorry, you are not allowed to do that.',
      'data' => ['status' => 401],
    ], $data);
  }

  public function testCreateWorkflow(): void {
    $data = $this->post(self::ENDPOINT_PATH, [
      'json' => [
        'name' => 'Testing workflow',
        'steps' => [
          'e6d193766b9ecd1e' => [
            'id' => 'e6d193766b9ecd1e',
            'type' => 'action',
            'key' => 'core:wait',
            'args' => ['seconds' => 60],
          ],
          '0d618edf689909ef' => [
            'id' => '0d618edf689909ef',
            'type' => 'trigger',
            'key' => 'mailpoet:segment:subscribed',
            'next_step_id' => 'e6d193766b9ecd1e',
          ],
        ],
      ],
    ]);

    $this->assertSame(['data' => null], $data);
  }
}
