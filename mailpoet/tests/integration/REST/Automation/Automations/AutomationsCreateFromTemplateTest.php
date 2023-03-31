<?php declare(strict_types = 1);

namespace MailPoet\REST\Automation\Automations;

require_once __DIR__ . '/../AutomationTest.php';

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\DI\ContainerWrapper;
use MailPoet\REST\Automation\AutomationTest;

class AutomationsCreateFromTemplateTest extends AutomationTest {
  private const ENDPOINT_PATH = '/mailpoet/v1/automations/create-from-template';

  /** @var AutomationStorage */
  private $automationStorage;

  public function _before() {
    parent::_before();
    $this->automationStorage = $this->diContainer->get(AutomationStorage::class);
  }

  public function testCreateAutomationFromTemplate(): void {
    $countBefore = count($this->automationStorage->getAutomations());
    $this->post(self::ENDPOINT_PATH, [
      'json' => [
        'slug' => 'subscriber-welcome-email',
      ],
    ]);
    $countAfter = count($this->automationStorage->getAutomations());
    expect($countAfter)->equals($countBefore + 1);
  }

  public function testEditorIsAllowed(): void {
    wp_set_current_user($this->editorUserId);
    $countBefore = count($this->automationStorage->getAutomations());
    $data = $this->post(self::ENDPOINT_PATH, [
      'json' => [
        'slug' => 'subscriber-welcome-email',
      ],
    ]);
    $countAfter = count($this->automationStorage->getAutomations());
    $this->assertEquals($countBefore + 1, $countAfter);

    $this->assertSame("Welcome new subscribers", $data['data']['name']);
  }

  public function testGuestNotAllowed(): void {
    wp_set_current_user(0);
    $countBefore = count($this->automationStorage->getAutomations());
    $data = $this->post(self::ENDPOINT_PATH, [
      'json' => [
        'slug' => 'subscriber-welcome-email',
      ],
    ]);
    $countAfter = count($this->automationStorage->getAutomations());
    $this->assertEquals($countBefore, $countAfter);

    $this->assertSame([
      'code' => 'rest_forbidden',
      'message' => 'Sorry, you are not allowed to do that.',
      'data' => ['status' => 401],
    ], $data);
  }

  public function testAutomationsCreatedFromTemplatesAreCreatedInDraftStatus(): void {
    $storage = ContainerWrapper::getInstance()->get(AutomationStorage::class);
    $this->post(self::ENDPOINT_PATH, [
      'json' => [
        'slug' => 'subscriber-welcome-email',
      ],
    ]);
    $allAutomations = $storage->getAutomations();
    $createdAutomation = array_pop($allAutomations);
    expect($createdAutomation->getStatus())->equals('draft');
  }

  public function testAutomationsCreatedFromTemplatesReturnsAutomationId(): void {
    $response = $this->post(self::ENDPOINT_PATH, [
      'json' => [
        'slug' => 'subscriber-welcome-email',
      ],
    ]);
    $allAutomations = $this->automationStorage->getAutomations();
    $createdAutomation = array_pop($allAutomations);
    $this->assertInstanceOf(Automation::class, $createdAutomation);
    $this->assertSame($createdAutomation->getId(), $response['data']['id']);
  }

  public function _after() {
    parent::_after();
    $this->automationStorage->truncate();
  }
}
