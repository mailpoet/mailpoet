<?php declare(strict_types = 1);

namespace MailPoet\Abilities;

use MailPoet\Features\FeaturesController;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

/**
 * Registers MailPoet abilities through the core Abilities API, independent of
 * WooCommerce. Write abilities stay behind the mcp_write_abilities feature flag.
 */
class AbilitiesRegistrar {
  public const CATEGORY = 'mailpoet';

  public const ABILITY_NAMES = [
    SendingStatusAbility::NAME,
    UnsubscribeTrendAbility::NAME,
    RecentNewslettersAbility::NAME,
  ];

  public const WRITE_ABILITY_NAMES = [
    CreateNewsletterDraftAbility::NAME,
  ];

  /** @var WPFunctions */
  private $wp;

  /** @var FeaturesController */
  private $featuresController;

  /** @var Ability[] */
  private $abilities;

  /** @var Ability[] */
  private $writeAbilities;

  public function __construct(
    WPFunctions $wp,
    FeaturesController $featuresController,
    SendingStatusAbility $sendingStatusAbility,
    UnsubscribeTrendAbility $unsubscribeTrendAbility,
    RecentNewslettersAbility $recentNewslettersAbility,
    CreateNewsletterDraftAbility $createNewsletterDraftAbility
  ) {
    $this->wp = $wp;
    $this->featuresController = $featuresController;
    $this->abilities = [
      $sendingStatusAbility,
      $unsubscribeTrendAbility,
      $recentNewslettersAbility,
    ];
    $this->writeAbilities = [
      $createNewsletterDraftAbility,
    ];
  }

  public function init(): void {
    $this->wp->addAction('wp_abilities_api_categories_init', [$this, 'registerCategory']);
    $this->wp->addAction('wp_abilities_api_init', [$this, 'registerAbilities']);
  }

  public function registerCategory(): void {
    $this->wp->registerAbilityCategory(self::CATEGORY, [
      'label' => __('MailPoet', 'mailpoet'),
      'description' => __('Read MailPoet email marketing data: sending health, subscriber limits, and email statistics.', 'mailpoet'),
    ]);
  }

  public function registerAbilities(): void {
    $abilities = $this->abilities;
    if ($this->featuresController->isSupported(FeaturesController::FEATURE_MCP_WRITE_ABILITIES)) {
      $abilities = array_merge($abilities, $this->writeAbilities);
    }
    foreach ($abilities as $ability) {
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

  public static function getWriteMeta(): array {
    return [
      'show_in_rest' => true,
      'mcp' => [
        'public' => true,
        'type' => 'tool',
      ],
      'annotations' => [
        'readonly' => false,
        'destructive' => false,
        'idempotent' => false,
      ],
    ];
  }
}
