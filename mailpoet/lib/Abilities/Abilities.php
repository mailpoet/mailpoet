<?php declare(strict_types = 1);

namespace MailPoet\Abilities;

use Automattic\WooCommerce\Abilities\AbilityDefinition;

if (!defined('ABSPATH')) exit;

class Abilities {
  public static function init(): void {
    add_action('plugins_loaded', [self::class, 'register'], 20);
  }

  public static function register(): void {
    if (!interface_exists(AbilityDefinition::class)) {
      return;
    }

    require_once __DIR__ . '/WooCommerceAutomationTemplates.php';
    require_once __DIR__ . '/WooCommerceMarketingStatus.php';

    add_filter('woocommerce_ability_definition_classes', [self::class, 'addAbilityDefinitionClasses']);
  }

  public static function addAbilityDefinitionClasses(array $classes): array {
    $classes[] = WooCommerceAutomationTemplates::class;
    $classes[] = WooCommerceMarketingStatus::class;

    return $classes;
  }
}
