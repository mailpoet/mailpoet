<?php

namespace MailPoet\REST\Automation\Workflows;

use MailPoet\Automation\Engine\Data\WorkflowTemplate;
use MailPoet\REST\Automation\AutomationTest;

require_once __DIR__ . '/../AutomationTest.php';

class WorkflowTemplatesGetEndpointTest extends AutomationTest
{
  private const ENDPOINT_PATH = '/mailpoet/v1/automation/workflow-templates';

  public function testGetAllTemplates() {
    $result = $this->get(self::ENDPOINT_PATH, []);
    $this->assertCount(7, $result['data']);
    $this->assertEquals('subscriber-welcome-email', $result['data'][0]['slug']);
  }

  public function testGuestNotAllowed(): void {
    wp_set_current_user(0);
    $data = $this->get(self::ENDPOINT_PATH, []);

    $this->assertSame([
      'code' => 'rest_forbidden',
      'message' => 'Sorry, you are not allowed to do that.',
      'data' => ['status' => 401],
    ], $data);
  }

  public function testGetTemplatesByCategory() {
    //@ToDo: Once we have templates in other categories, we should make this test more specific.
    $result = $this->get(self::ENDPOINT_PATH, [
      'json' => [
        'category' => WorkflowTemplate::CATEGORY_WELCOME,
      ],
    ]);
    $this->assertCount(4, $result['data']);
    $result = $this->get(self::ENDPOINT_PATH, [
      'json' => [
        'category' => WorkflowTemplate::CATEGORY_ABANDONED_CART,
      ],
    ]);
    $this->assertCount(1, $result['data']);
    $result = $this->get(self::ENDPOINT_PATH, [
      'json' => [
        'category' => WorkflowTemplate::CATEGORY_WOOCOMMERCE,
      ],
    ]);
    $this->assertCount(2, $result['data']);
  }
}
