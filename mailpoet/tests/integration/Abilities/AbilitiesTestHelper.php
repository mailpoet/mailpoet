<?php declare(strict_types = 1);

namespace MailPoet\Test\Abilities;

use MailPoet\Abilities\AbilitiesRegistrar;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Features\FeaturesController;
use MailPoet\Test\DataFactories\Features;

class AbilitiesTestHelper {
  public static function enableFeature(): void {
    (new Features())->withFeatureEnabled(FeaturesController::FEATURE_MCP_ABILITIES);
    ContainerWrapper::getInstance()->get(FeaturesController::class)->resetCache();
  }

  /**
   * Core only accepts registrations while its init actions run, and the registries
   * were initialized before the test started, so the actions are simulated here.
   */
  public static function register(AbilitiesRegistrar $registrar): void {
    // The flag is stored in the database, so a previous run can leave it on and the
    // abilities registered while WordPress booted.
    self::removeRegistrations();
    self::runInsideAction('wp_abilities_api_categories_init', [$registrar, 'registerCategory']);
    self::runInsideAction('wp_abilities_api_init', [$registrar, 'registerAbilities']);
  }

  public static function unregister(): void {
    (new Features())->withFeatureDisabled(FeaturesController::FEATURE_MCP_ABILITIES);
    ContainerWrapper::getInstance()->get(FeaturesController::class)->resetCache();
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
