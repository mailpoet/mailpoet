<?php declare(strict_types = 1);

namespace MailPoet\REST\Automation\Automations;

use MailPoet\REST\Automation\AutomationTest;

require_once __DIR__ . '/../AutomationTest.php';

/**
 * @group woo
 */
class AutomationTemplatesGetEndpointTest extends AutomationTest {

  private const ENDPOINT_PATH = '/mailpoet/v1/automation-templates';

  public function testGetAllTemplates() {
    $result = $this->get(self::ENDPOINT_PATH, []);
    $this->assertCount(8, $result['data']);
    $this->assertEquals('subscriber-welcome-email', $result['data'][0]['slug']);
  }

  public function testEditorIsAllowed(): void {
    wp_set_current_user($this->editorUserId);
    $data = $this->get(self::ENDPOINT_PATH, []);

    $this->assertCount(8, $data['data']);
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
        'category' => 'welcome',
      ],
    ]);

    $this->assertCount(4, $result['data']);
    $result = $this->get(self::ENDPOINT_PATH, [
      'json' => [
        'category' => 'abandoned-cart',
      ],
    ]);
    $this->assertCount(2, $result['data']);
    $result = $this->get(self::ENDPOINT_PATH, [
      'json' => [
        'category' => 'woocommerce',
      ],
    ]);
    $this->assertCount(2, $result['data']);
  }
}
