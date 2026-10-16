<?php declare(strict_types = 1);

namespace MailPoet\Abilities;

use MailPoet\Features\FeaturesController;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

/**
 * Registers read-only MailPoet abilities through the core Abilities API,
 * independent of WooCommerce, behind the mcp_abilities feature flag.
 */
class AbilitiesRegistrar {
  public const CATEGORY = 'mailpoet';

  public const ABILITY_NAMES = [
    SendingStatusAbility::NAME,
    UnsubscribeTrendAbility::NAME,
    RecentNewslettersAbility::NAME,
  ];

  /** @var FeaturesController */
  private $featuresController;

  /** @var WPFunctions */
  private $wp;

  /** @var Ability[] */
  private $abilities;

  public function __construct(
    FeaturesController $featuresController,
    WPFunctions $wp,
    SendingStatusAbility $sendingStatusAbility,
    UnsubscribeTrendAbility $unsubscribeTrendAbility,
    RecentNewslettersAbility $recentNewslettersAbility
  ) {
    $this->featuresController = $featuresController;
    $this->wp = $wp;
    $this->abilities = [
      $sendingStatusAbility,
      $unsubscribeTrendAbility,
      $recentNewslettersAbility,
    ];
  }

  public function init(): void {
    $this->wp->addAction('wp_abilities_api_categories_init', [$this, 'registerCategory']);
    $this->wp->addAction('wp_abilities_api_init', [$this, 'registerAbilities']);
  }

  public function registerCategory(): void {
    if (!$this->isEnabled()) {
      return;
    }
    $this->wp->registerAbilityCategory(self::CATEGORY, [
      'label' => __('MailPoet', 'mailpoet'),
      'description' => __('Read MailPoet email marketing data: sending health, subscriber limits, and email statistics.', 'mailpoet'),
    ]);
  }

  public function registerAbilities(): void {
    if (!$this->isEnabled()) {
      return;
    }
    foreach ($this->abilities as $ability) {
      $this->wp->registerAbility(
        $ability->getName(),
        array_merge($ability->getRegistrationArgs(), ['category' => self::CATEGORY])
      );
    }
  }

  public static function getReadOnlyMeta(): array {
    return [
      'show_in_rest' => true,
      'mcp' => [
        'public' => true,
        'type' => 'tool',
      ],
      'annotations' => [
        'readonly' => true,
        'destructive' => false,
        'idempotent' => true,
      ],
    ];
  }

  private function isEnabled(): bool {
    return $this->featuresController->isSupported(FeaturesController::FEATURE_MCP_ABILITIES);
  }
}
