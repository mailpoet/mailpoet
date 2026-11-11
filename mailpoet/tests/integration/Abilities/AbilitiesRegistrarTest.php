<?php declare(strict_types = 1);

namespace MailPoet\Test\Abilities;

use MailPoet\Abilities\AbilitiesRegistrar;

require_once __DIR__ . '/AbilitiesTestHelper.php';

class AbilitiesRegistrarTest extends \MailPoetTest {
  /** @var AbilitiesRegistrar */
  private $registrar;

  public function _before(): void {
    $this->registrar = $this->diContainer->get(AbilitiesRegistrar::class);
  }

  public function _after(): void {
    AbilitiesTestHelper::unregister();
    parent::_after();
  }

  public function testItRegistersReadOnlyMcpAbilitiesInMailPoetCategory(): void {
    AbilitiesTestHelper::register($this->registrar);

    verify(wp_has_ability_category('mailpoet'))->true();
    verify(AbilitiesRegistrar::ABILITY_NAMES)->equals([
      'mailpoet/get-sending-status',
      'mailpoet/get-unsubscribe-trend',
      'mailpoet/list-recent-newsletters',
    ]);
    foreach (AbilitiesRegistrar::ABILITY_NAMES as $name) {
      $ability = wp_get_ability($name);
      $this->assertInstanceOf(\WP_Ability::class, $ability, $name);
      $meta = $ability->get_meta();
      verify($ability->get_category())->equals('mailpoet');
      verify($meta['show_in_rest'])->true();
      verify($meta['mcp'])->equals(['public' => true, 'type' => 'tool']);
      verify($meta['annotations'])->equals(['readonly' => true, 'destructive' => false, 'idempotent' => true]);
    }
  }

  public function testItKeepsDescriptionsShort(): void {
    AbilitiesTestHelper::register($this->registrar);

    // MCP clients load every description into the model's context on each request.
    foreach (AbilitiesRegistrar::ABILITY_NAMES as $name) {
      $ability = wp_get_ability($name);
      $this->assertInstanceOf(\WP_Ability::class, $ability, $name);
      $this->assertLessThanOrEqual(500, strlen($ability->get_description()), $name);
    }
  }
}
