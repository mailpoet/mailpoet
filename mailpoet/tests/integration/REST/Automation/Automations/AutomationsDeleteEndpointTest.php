<?php declare(strict_types = 1);

namespace MailPoet\REST\Automation\Automations;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\REST\Automation\AutomationTest;

require_once __DIR__ . '/../AutomationTest.php';

class AutomationsDeleteEndpointTest extends AutomationTest {
  private const ENDPOINT_PATH = '/mailpoet/v1/automation/automations/%d';

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var Automation */
  private $automation;

  public function _before() {
    parent::_before();
    $this->automationStorage = $this->diContainer->get(AutomationStorage::class);
    $id = $this->automationStorage->createAutomation(
      new Automation(
        'Testing automation',
        ['root' => new Step('root', Step::TYPE_ROOT, 'core:root', [], [])],
        wp_get_current_user()
      )
    );
    $automation = $this->automationStorage->getAutomation($id);
    $this->assertInstanceOf(Automation::class, $automation);
    $this->automation = $automation;
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
      'code' => 'mailpoet_automation_automation_not_trashed',
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
    $this->automationStorage->truncate();
    parent::_after();
  }
}
