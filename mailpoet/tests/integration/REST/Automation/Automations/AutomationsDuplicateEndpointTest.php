<?php declare(strict_types = 1);

namespace MailPoet\REST\Automation\Automations;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\REST\Automation\AutomationTest;

require_once __DIR__ . '/../AutomationTest.php';

//phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

class AutomationsDuplicateEndpointTest extends AutomationTest {
  private const ENDPOINT_PATH = '/mailpoet/v1/automations/%d/duplicate';

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

  public function testEditorIsAllowed(): void {
    wp_set_current_user($this->editorUserId);
    $data = $this->post(sprintf(self::ENDPOINT_PATH, $this->automation->getId()));

    $this->assertSame('Copy of Testing automation', $data['data']['name']);
    $this->assertNotNull($this->automationStorage->getAutomation($this->automation->getId() + 1));
  }

  public function testGuestNotAllowed(): void {
    wp_set_current_user(0);
    $data = $this->post(sprintf(self::ENDPOINT_PATH, $this->automation->getId()));

    $this->assertSame([
      'code' => 'rest_forbidden',
      'message' => 'Sorry, you are not allowed to do that.',
      'data' => ['status' => 401],
    ], $data);

    $automation = $this->automationStorage->getAutomation($this->automation->getId());
    $this->assertInstanceOf(Automation::class, $automation);
    $this->assertSame('Testing automation', $automation->getName());
    $this->assertNull($this->automationStorage->getAutomation($this->automation->getId() + 1));
  }

  public function testItDuplicatesAnAutomation(): void {
    $data = $this->post(sprintf(self::ENDPOINT_PATH, $this->automation->getId()));

    $id = $this->automation->getId() + 1;
    $user = wp_get_current_user();
    $createdAt = DateTimeImmutable::createFromFormat(DateTimeImmutable::W3C, $data['data']['created_at'] ?? null);
    $updatedAt = DateTimeImmutable::createFromFormat(DateTimeImmutable::W3C, $data['data']['updated_at'] ?? null);

    $this->assertInstanceOf(DateTimeImmutable::class, $createdAt);
    $this->assertInstanceOf(DateTimeImmutable::class, $updatedAt);
    $this->assertEquals($createdAt, $updatedAt);

    $expected = [
      'id' => $id,
      'name' => 'Copy of Testing automation',
      'status' => 'draft',
      'created_at' => $createdAt->format(DateTimeImmutable::W3C),
      'updated_at' => $updatedAt->format(DateTimeImmutable::W3C),
      'activated_at' => null,
      'author' => [
        'id' => $user->ID,
        'name' => $user->display_name,
      ],
      'stats' => [
        'automation_id' => $id,
        'totals' => [
          'entered' => 0,
          'in_progress' => 0,
          'exited' => 0,
        ],
      ],
      'steps' => [
        'root' => [
          'id' => 'root',
          'type' => 'root',
          'key' => 'core:root',
          'args' => [],
          'next_steps' => [],
        ],
      ],
      'meta' => [],
    ];
    $this->assertSame(['data' => $expected], $data);

    $expectedAutomation = Automation::fromArray(
      array_merge($expected, ['steps' => json_encode($expected['steps']), 'version_id' => 1])
    );
    $automation = $this->automationStorage->getAutomation($id);
    $this->assertInstanceOf(Automation::class, $automation);
    $this->assertTrue($automation->equals($expectedAutomation));
  }

  public function _after() {
    parent::_after();
    $this->automationStorage->truncate();
  }
}
