<?php declare(strict_types = 1);

namespace MailPoet\REST\Automation\Automations;

use MailPoet\Automation\Engine\Builder\CreateAutomationFromTemplateController;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\REST\Automation\AutomationTest;

require_once __DIR__ . '/../AutomationTest.php';

class AutomationPutEndpointTest extends AutomationTest {

  private const ENDPOINT_PATH = '/mailpoet/v1/automations/%d';

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var CreateAutomationFromTemplateController */
  private $createAutomation;

  /** @var Automation */
  private $automation;

  public function _before() {
    parent::_before();
    $this->automationStorage = $this->diContainer->get(AutomationStorage::class);
    $this->createAutomation = $this->diContainer->get(CreateAutomationFromTemplateController::class);
    $this->automation = $this->createAutomation->createAutomation('subscriber-welcome-email');
    $this->assertInstanceOf(Automation::class, $this->automation);
  }

  public function testEditorIsAllowed(): void {
    wp_set_current_user($this->editorUserId);
    $data = $this->put(
      sprintf(self::ENDPOINT_PATH, $this->automation->getId()),
      [
        'json' => [
          'name' => 'Test',
        ],
      ]
    );

    $this->assertSame("Test", $data['data']['name']);

    $automation = $this->automationStorage->getAutomation($this->automation->getId());
    $this->assertInstanceOf(Automation::class, $automation);
    $this->assertSame('Test', $automation->getName());
  }

  public function testGuestNotAllowed(): void {
    wp_set_current_user(0);
    $data = $this->put(
      sprintf(self::ENDPOINT_PATH, $this->automation->getId()),
      [
        'json' => [
          'name' => 'Test',
        ],
      ]
    );

    $this->assertSame([
      'code' => 'rest_forbidden',
      'message' => 'Sorry, you are not allowed to do that.',
      'data' => ['status' => 401],
    ], $data);

    $automation = $this->automationStorage->getAutomation($this->automation->getId());
    $this->assertInstanceOf(Automation::class, $automation);
    $this->assertSame('Welcome new subscribers', $automation->getName());
  }

  public function testUpdateAutomation(): void {
    $changes = [];
    $trigger = $this->automation->getTrigger('mailpoet:someone-subscribes');
    $this->assertInstanceOf(Step::class, $trigger);
    $changes[$trigger->getId()] = [
      'args' => [
        'segment_ids' => [1,2],
      ],
    ];
    $updatedSteps = $this->getChangedStepsStructureOfAutomation($this->automation, $changes);
    $data = $this->put(
      sprintf(self::ENDPOINT_PATH, $this->automation->getId()),
      [
        'json' => [
          'name' => 'Test',
          'steps' => $updatedSteps,
        ],
      ]
    );

    $updatedAutomation = $this->automationStorage->getAutomation($this->automation->getId());
    $this->assertInstanceOf(Automation::class, $updatedAutomation);
    $updatedTrigger = $updatedAutomation->getTrigger('mailpoet:someone-subscribes');
    $this->assertInstanceOf(Step::class, $updatedTrigger);

    /** Ensure the old automation does not already contain the values we attempt to change */
    $this->assertNotSame($changes[$trigger->getId()]['args'], $trigger->getArgs());
    $this->assertNotSame('test', $this->automation->getName());

    /** Ensure, the changes have been stored to the database */
    $this->assertSame('Test', $updatedAutomation->getName());
    $this->assertSame($changes[$trigger->getId()]['args'], $updatedTrigger->getArgs());

    /** Ensure the updated automation gets returned from the endpoint */
    $this->assertSame('Test', $data['data']['name']);
  }

  public function testAutomationBasicValidationWorks(): void {
    $data = $this->put(
      sprintf(self::ENDPOINT_PATH, $this->automation->getId()),
      [
        'json' => [
          'steps' => [
            'root' => [
              'id' => 'root',
              'key' => 'core:root',
              'type' => Step::TYPE_ROOT,
              'args' => [],
              'next_steps' => [],
            ],
          ],
        ],
      ]
    );

    $this->assertSame('mailpoet_automation_structure_modification_not_supported', $data['code']);
    $automation = $this->automationStorage->getAutomation($this->automation->getId());
    $this->assertInstanceOf(Automation::class, $automation);
    /** Ensure, the changes have not been stored to the database */
    $this->assertSame($this->automation->getVersionId(), $automation->getVersionId());
  }

  private function getChangedStepsStructureOfAutomation(Automation $automation, array $changes = []) {
    $steps = $automation->getSteps();
    $data = [];
    foreach ($steps as $step) {
      $data[$step->getId()] = array_merge(
        $step->toArray(),
        $changes[$step->getId()] ?? []
      );
    }
    return $data;
  }

  public function _after() {
    parent::_after();
    $this->automationStorage->truncate();
  }
}
