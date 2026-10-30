<?php declare(strict_types = 1);

namespace MailPoet\Test\Abilities;

use MailPoet\Abilities\AbilitiesRegistrar;

class AbilitiesTestHelper {
  /**
   * Core only accepts registrations while its init actions run, and the registries
   * were initialized before the test started, so the actions are simulated here.
   */
  public static function register(AbilitiesRegistrar $registrar): void {
    // WordPress registered the abilities while it booted.
    self::removeRegistrations();
    self::runInsideAction('wp_abilities_api_categories_init', [$registrar, 'registerCategory']);
    self::runInsideAction('wp_abilities_api_init', [$registrar, 'registerAbilities']);
  }

  public static function unregister(): void {
    self::removeRegistrations();
  }

  private static function removeRegistrations(): void {
    foreach (AbilitiesRegistrar::ABILITY_NAMES as $name) {
      if (wp_has_ability($name)) {
        wp_unregister_ability($name);
      }
    }
    if (wp_has_ability_category(AbilitiesRegistrar::CATEGORY)) {
      wp_unregister_ability_category(AbilitiesRegistrar::CATEGORY);
    }
  }

  private static function runInsideAction(string $hook, callable $callback): void {
    global $wp_current_filter;
    $wp_current_filter[] = $hook;
    try {
      $callback();
    } finally {
      array_pop($wp_current_filter);
    }
  }
}
