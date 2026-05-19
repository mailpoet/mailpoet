<?php declare(strict_types = 1);

namespace MailPoet\REST\Automation\Automations;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\REST\Automation\AutomationTest;

require_once __DIR__ . '/../AutomationTest.php';

class AutomationVersionsGetEndpointTest extends AutomationTest {
  private const ENDPOINT_PATH = '/mailpoet/v1/automations/%d/versions';

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var Automation */
  private $automation;

  public function _before() {
    parent::_before();
    $this->automationStorage = $this->diContainer->get(AutomationStorage::class);
    $this->automation = $this->tester->createAutomation('Test automation');
  }

  public function testEditorIsAllowed(): void {
    wp_set_current_user($this->editorUserId);

    $data = $this->get(sprintf(self::ENDPOINT_PATH, $this->automation->getId()));

    $this->assertCount(1, $data['data']['items']);
  }

  public function testGuestNotAllowed(): void {
    wp_set_current_user(0);

    $data = $this->get(sprintf(self::ENDPOINT_PATH, $this->automation->getId()));

    $this->assertSame([
      'code' => 'rest_forbidden',
      'message' => 'Sorry, you are not allowed to do that.',
      'data' => ['status' => 401],
    ], $data);
  }

  public function testItReturnsVersionDataWithCurrentFlag(): void {
    $this->automation->setName('Updated automation');
    $this->automationStorage->updateAutomation($this->automation);
    $updatedAutomation = $this->automationStorage->getAutomation($this->automation->getId());
    $this->assertInstanceOf(Automation::class, $updatedAutomation);

    $data = $this->get(sprintf(self::ENDPOINT_PATH, $this->automation->getId()));

    $this->assertCount(2, $data['data']['items']);
    $this->assertSame($updatedAutomation->getVersionId(), $data['data']['items'][0]['id']);
    $this->assertTrue($data['data']['items'][0]['is_current']);
    $this->assertFalse($data['data']['items'][1]['is_current']);
    $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $data['data']['items'][0]['created_at']);
  }
}
