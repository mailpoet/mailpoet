<?php declare(strict_types = 1);

namespace MailPoet\Test\Abilities;

use MailPoet\Abilities\AbilitiesRegistrar;
use MailPoet\Abilities\CreateNewsletterDraftAbility;
use MailPoet\Abilities\GetNewsletterDraftAbility;
use MailPoet\Abilities\NewsletterDraftOptionsAbility;
use MailPoet\Abilities\UpdateNewsletterDraftAbility;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Features\FeaturesController;
use MailPoet\Test\DataFactories\Features;

class AbilitiesTestHelper {
  public const DRAFT_ABILITY_NAMES = [
    CreateNewsletterDraftAbility::NAME,
    NewsletterDraftOptionsAbility::NAME,
    GetNewsletterDraftAbility::NAME,
    UpdateNewsletterDraftAbility::NAME,
  ];

  public static function enableDraftAbilities(): void {
    (new Features())->withFeatureEnabled(FeaturesController::FEATURE_MCP_WRITE_ABILITIES);
    self::resetFeatureCache();
    self::register(ContainerWrapper::getInstance()->get(AbilitiesRegistrar::class));
  }

  public static function disableDraftAbilities(): void {
    self::unregister();
    self::resetFeatureCache();
    self::deleteEmailPosts();
  }

  public static function resetFeatureCache(): void {
    ContainerWrapper::getInstance()->get(FeaturesController::class)->resetCache();
  }

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

  /** WordPress posts aren't emptied between tests like the MailPoet tables are. */
  private static function deleteEmailPosts(): void {
    $ids = get_posts([
      'post_type' => 'mailpoet_email',
      'post_status' => ['draft', 'sent'],
      'numberposts' => -1,
      'fields' => 'ids',
    ]);
    array_map(function ($id) {
      wp_delete_post((int)$id, true);
    }, $ids);
  }

  private static function removeRegistrations(): void {
    foreach (array_merge(AbilitiesRegistrar::ABILITY_NAMES, self::DRAFT_ABILITY_NAMES) as $name) {
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
