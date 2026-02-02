<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns;

use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\AbandonedCartPattern;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\AbandonedCartWithDiscountPattern;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\EducationalCampaignPattern;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\EventInvitationPattern;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\FirstPurchaseThankYouPattern;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\NewArrivalsAnnouncementPattern;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\NewProductsAnnouncementPattern;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\NewsletterPattern;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\PostPurchaseThankYouPattern;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\ProductPurchaseFollowUpPattern;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\ProductRestockNotificationPattern;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\SaleAnnouncementPattern;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\WelcomeEmailPattern;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\WelcomeWithDiscountEmailPattern;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\WinBackCustomerPattern;
use MailPoet\Util\CdnAssetUrl;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;

class PatternsController {
  private const MIN_WOOCOMMERCE_VERSION_FOR_COUPON_BLOCK = '10.5.0';

  private CdnAssetUrl $cdnAssetUrl;
  private WPFunctions $wp;
  private WooCommerceHelper $wooCommerceHelper;

  /** @var Pattern[] */
  private array $patterns = [];

  public function __construct(
    CdnAssetUrl $cdnAssetUrl,
    WPFunctions $wp,
    WooCommerceHelper $wooCommerceHelper
  ) {
    $this->cdnAssetUrl = $cdnAssetUrl;
    $this->wp = $wp;
    $this->wooCommerceHelper = $wooCommerceHelper;
  }

  /**
   * Get the content of a pattern by name.
   *
   * @param string $patternName The pattern name (e.g., 'welcome-email-content')
   * @return string|null The pattern content or null if not found
   */
  public function getPatternContent(string $patternName): ?string {
    $this->ensurePatternsInitialized();

    foreach ($this->patterns as $pattern) {
      if ($pattern->get_name() === $patternName) {
        // Apply the same filter used in registerPatterns for consistency
        $patternData = $this->wp->applyFilters('mailpoet_email_editor_integration_register_pattern', [
          'name' => $pattern->get_namespace() . '/' . $pattern->get_name(),
          'properties' => $pattern->get_properties(),
        ], $pattern);

        if (!is_array($patternData) || !isset($patternData['properties'])) {
          return null;
        }
        return $patternData['properties']['content'] ?? null;
      }
    }

    return null;
  }

  private function ensurePatternsInitialized(): void {
    if (!empty($this->patterns)) {
      return;
    }

    $this->patterns = [
      new NewsletterPattern($this->cdnAssetUrl),
      new SaleAnnouncementPattern($this->cdnAssetUrl),
      new NewProductsAnnouncementPattern($this->cdnAssetUrl),
      new EducationalCampaignPattern($this->cdnAssetUrl),
      new EventInvitationPattern($this->cdnAssetUrl),
      new ProductRestockNotificationPattern($this->cdnAssetUrl),
      new NewArrivalsAnnouncementPattern($this->cdnAssetUrl),
      new WelcomeEmailPattern($this->cdnAssetUrl),
    ];

    // WooCommerce-dependent patterns (uses product blocks or purchase/abandoned-cart categories)
    if ($this->wooCommerceHelper->isWooCommerceActive()) {
      $this->patterns = array_merge($this->patterns, [
        new FirstPurchaseThankYouPattern($this->cdnAssetUrl),
        new PostPurchaseThankYouPattern($this->cdnAssetUrl),
        new ProductPurchaseFollowUpPattern($this->cdnAssetUrl),
        new AbandonedCartPattern($this->cdnAssetUrl),
      ]);

      // Patterns using the coupon block require WooCommerce 10.5.0+
      $wooCommerceVersion = $this->wooCommerceHelper->getWooCommerceVersion();
      // Strip pre-release suffixes (e.g., -rc1, -beta1) to ensure RC/beta versions pass the check
      $wooCommerceVersion = $wooCommerceVersion ? preg_replace('/[^0-9.].*/', '', $wooCommerceVersion) : null;
      if ($wooCommerceVersion && version_compare($wooCommerceVersion, self::MIN_WOOCOMMERCE_VERSION_FOR_COUPON_BLOCK, '>=')) {
        $this->patterns = array_merge($this->patterns, [
          new WelcomeWithDiscountEmailPattern($this->cdnAssetUrl),
          new WinBackCustomerPattern($this->cdnAssetUrl),
          new AbandonedCartWithDiscountPattern($this->cdnAssetUrl),
        ]);
      }
    }
  }

  public function registerPatterns(): void {
    $this->registerPatternCategories();
    $this->ensurePatternsInitialized();

    foreach ($this->patterns as $pattern) {
      $patternName = $pattern->get_namespace() . '/' . $pattern->get_name();
      $patternProperties = $pattern->get_properties();

      /**
       * Filters pattern data before it is registered as a block pattern.
       *
       * @param array{name: string, properties: array} $patternData Pattern name and properties.
       * @param Pattern $pattern The original Pattern object.
       * @return array|null Return modified data or null/false to skip registration.
       */
      $patternData = $this->wp->applyFilters('mailpoet_email_editor_integration_register_pattern', [
        'name' => $patternName,
        'properties' => $patternProperties,
      ], $pattern);

      if (is_array($patternData) && isset($patternData['name']) && isset($patternData['properties'])) {
        register_block_pattern($patternData['name'], $patternData['properties']);
      }
    }
  }

  private function registerPatternCategories(): void {
    $categories = [
      [
        'name' => 'newsletter',
        'label' => _x('Newsletter', 'Block pattern category', 'mailpoet'),
        'description' => __('A collection of newsletter email layouts.', 'mailpoet'),
      ],
      [
        'name' => 'welcome',
        'label' => _x('Welcome', 'Block pattern category', 'mailpoet'),
        'description' => __('A collection of welcome email layouts.', 'mailpoet'),
      ],
    ];

    // WooCommerce-dependent categories
    if ($this->wooCommerceHelper->isWooCommerceActive()) {
      $categories[] = [
        'name' => 'purchase',
        'label' => _x('Post-purchase', 'Block pattern category', 'mailpoet'),
        'description' => __('A collection of post-purchase email layouts.', 'mailpoet'),
      ];
      $categories[] = [
        'name' => 'abandoned-cart',
        'label' => _x('Abandoned cart', 'Block pattern category', 'mailpoet'),
        'description' => __('A collection of abandoned cart email layouts.', 'mailpoet'),
      ];
    }

    foreach ($categories as $category) {
      register_block_pattern_category($category['name'], [
        'label' => $category['label'],
        'description' => $category['description'],
      ]);
    }
  }
}
