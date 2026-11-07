<?php declare(strict_types = 1);

namespace MailPoet\Abilities;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Features\FeaturesController;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

/**
 * Registers MailPoet abilities through the core Abilities API, independent of
 * WooCommerce. The abilities for newsletter drafts stay behind the
 * mcp_write_abilities feature flag.
 */
class AbilitiesRegistrar {
  public const CATEGORY = 'mailpoet';

  public const ABILITY_NAMES = [
    SendingStatusAbility::NAME,
    UnsubscribeTrendAbility::NAME,
    RecentNewslettersAbility::NAME,
  ];

  private const EXPERIMENTAL_ABILITIES = [
    CreateNewsletterDraftAbility::class,
    NewsletterDraftOptionsAbility::class,
    GetNewsletterDraftAbility::class,
    UpdateNewsletterDraftAbility::class,
  ];

  /** @var WPFunctions */
  private $wp;

  /** @var FeaturesController */
  private $featuresController;

  /** @var Ability[] */
  private $abilities;

  /** @var ContainerWrapper */
  private $container;

  public function __construct(
    WPFunctions $wp,
    FeaturesController $featuresController,
    SendingStatusAbility $sendingStatusAbility,
    UnsubscribeTrendAbility $unsubscribeTrendAbility,
    RecentNewslettersAbility $recentNewslettersAbility,
    ContainerWrapper $container
  ) {
    $this->wp = $wp;
    $this->featuresController = $featuresController;
    $this->abilities = [
      $sendingStatusAbility,
      $unsubscribeTrendAbility,
      $recentNewslettersAbility,
    ];
    $this->container = $container;
  }

  public function init(): void {
    $this->wp->addAction('wp_abilities_api_categories_init', [$this, 'registerCategory']);
    $this->wp->addAction('wp_abilities_api_init', [$this, 'registerAbilities']);
  }

  public function registerCategory(): void {
    $this->wp->registerAbilityCategory(self::CATEGORY, [
      'label' => __('MailPoet', 'mailpoet'),
      'description' => __('MailPoet email marketing: sending health, subscriber limits and email statistics.', 'mailpoet'),
    ]);
  }

  public function registerAbilities(): void {
    $abilities = $this->abilities;
    // Built only here, so a site with the flag off doesn't load them on every request.
    if ($this->featuresController->isSupported(FeaturesController::FEATURE_MCP_WRITE_ABILITIES)) {
      foreach (self::EXPERIMENTAL_ABILITIES as $class) {
        $abilities[] = $this->container->get($class);
      }
    }
    foreach ($abilities as $ability) {
      $this->wp->registerAbility(
        $ability->getName(),
        array_merge($ability->getRegistrationArgs(), ['category' => self::CATEGORY])
      );
    }
  }

  public static function getReadOnlyMeta(): array {
    return self::getMeta(true, false, true);
  }

  public static function getWriteMeta(bool $destructive = false): array {
    return self::getMeta(false, $destructive, false);
  }

  private static function getMeta(bool $readonly, bool $destructive, bool $idempotent): array {
    return [
      'show_in_rest' => true,
      'mcp' => [
        'public' => true,
        'type' => 'tool',
      ],
      'annotations' => [
        'readonly' => $readonly,
        'destructive' => $destructive,
        'idempotent' => $idempotent,
      ],
    ];
  }
}
