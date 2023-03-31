<?php declare(strict_types = 1);

namespace MailPoet\REST\Automation\Automations;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\REST\Automation\AutomationTest;

require_once __DIR__ . '/../AutomationTest.php';

class AutomationsDeleteEndpointTest extends AutomationTest {
  private const ENDPOINT_PATH = '/mailpoet/v1/automations/%d';

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var Automation */
  private $automation;

  public function _before() {
    parent::_before();
    $this->automationStorage = $this->diContainer->get(AutomationStorage::class);
    $automation = $this->tester->createAutomation('Testing automation');
    $this->assertInstanceOf(Automation::class, $automation);
    $this->automation = $automation;
  }

  public function testEditorIsAllowed(): void {
    wp_set_current_user($this->editorUserId);
    $data = $this->delete(sprintf(self::ENDPOINT_PATH, $this->automation->getId()));

    $this->assertSame("mailpoet_automation_not_trashed", $data['code']);

  }

  public function testGuestNotAllowed(): void {
    wp_set_current_user(0);
    $data = $this->delete(sprintf(self::ENDPOINT_PATH, $this->automation->getId()));

    $this->assertSame([
      'code' => 'rest_forbidden',
      'message' => 'Sorry, you are not allowed to do that.',
      'data' => ['status' => 401],
    ], $data);

    $automation = $this->automationStorage->getAutomation($this->automation->getId());
    $this->assertInstanceOf(Automation::class, $automation);
    $this->assertSame('Testing automation', $automation->getName());
  }

  public function testCantDeleteAutomationWhenNotTrashed(): void {
    $data = $this->delete(sprintf(self::ENDPOINT_PATH, $this->automation->getId()));

    $this->assertSame([
      'code' => 'mailpoet_automation_not_trashed',
      'message' => "Can't delete automation with ID '{$this->automation->getId()}' because it was not trashed.",
      'data' => ['status' => 400, 'errors' => []],
    ], $data);

    $automation = $this->automationStorage->getAutomation($this->automation->getId());
    $this->assertInstanceOf(Automation::class, $automation);
    $this->assertSame('Testing automation', $automation->getName());
  }

  public function testItDeletesAAutomation(): void {
    $this->automation->setStatus(Automation::STATUS_TRASH);
    $this->automationStorage->updateAutomation($this->automation);

    $data = $this->delete(sprintf(self::ENDPOINT_PATH, $this->automation->getId()));
    $this->assertSame(['data' => null], $data);

    $automation = $this->automationStorage->getAutomation($this->automation->getId());
    $this->assertNull($automation);
  }

  public function _after() {
    parent::_after();
    $this->automationStorage->truncate();
  }
}
