<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns;

use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\AbandonedCartPattern;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\AbandonedCartReminderPattern;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\AbandonedCartWithDiscountPattern;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\AskForReviewPostPurchasePattern;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\BirthdayEmailPattern;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\CategoryPurchaseFollowUpPattern;
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
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\TagPurchaseFollowUpPattern;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\WelcomeEmailPattern;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\WelcomeWithDiscountEmailPattern;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\WinBackCustomerPattern;
use MailPoet\Util\CdnAssetUrl;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;

class PatternsController {
  private const MIN_WOOCOMMERCE_VERSION_FOR_GENERATED_COUPON_BLOCK = '10.8.0';

  private CdnAssetUrl $cdnAssetUrl;
  private WPFunctions $wp;
  private WooCommerceHelper $wooCommerceHelper;

  /** @var Pattern[] */
  private array $patterns = [];

  /** @var array<string, string> Pattern name → email content for patterns with split content */
  private array $emailContentRegistry = [];

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
   * Returns the email content (get_email_content()), which uses dynamic blocks
   * such as the WooCommerce product collection bound to the customer's cart
   * instead of the static placeholders shown in the editor's pattern preview.
   * For patterns without an email-specific variant this is identical to the
   * preview content.
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
          'email_content' => $pattern->get_email_content(),
        ], $pattern);

        if (!is_array($patternData)) {
          return null;
        }

        $emailContent = $patternData['email_content'] ?? null;
        if (is_string($emailContent) && $emailContent !== '') {
          return $emailContent;
        }

        $properties = $patternData['properties'] ?? null;
        $content = is_array($properties) ? ($properties['content'] ?? null) : null;
        return is_string($content) ? $content : null;
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
      new BirthdayEmailPattern($this->cdnAssetUrl),
    ];

    // WooCommerce-dependent patterns (uses product blocks or purchase/abandoned-cart categories)
    if ($this->wooCommerceHelper->isWooCommerceActive()) {
      $this->patterns = array_merge($this->patterns, [
        new FirstPurchaseThankYouPattern($this->cdnAssetUrl),
        new PostPurchaseThankYouPattern($this->cdnAssetUrl),
        new ProductPurchaseFollowUpPattern($this->cdnAssetUrl),
        new TagPurchaseFollowUpPattern($this->cdnAssetUrl),
        new CategoryPurchaseFollowUpPattern($this->cdnAssetUrl),
        new WinBackCustomerPattern($this->cdnAssetUrl, true),
        new WinBackCustomerPattern($this->cdnAssetUrl, false, true),
        new AbandonedCartPattern($this->cdnAssetUrl),
        new AbandonedCartReminderPattern($this->cdnAssetUrl),
        new AskForReviewPostPurchasePattern($this->cdnAssetUrl, 'positive-follow-up'),
        new AskForReviewPostPurchasePattern($this->cdnAssetUrl, 'negative-follow-up'),
      ]);

      if ($this->wooCommerceHelper->wcSupportsOrderReviewUrl()) {
        $this->patterns[] = new AskForReviewPostPurchasePattern($this->cdnAssetUrl);
      }

      // Patterns using generated coupons require WooCommerce 10.8.0+
      $wooCommerceVersion = $this->wooCommerceHelper->getWooCommerceVersion();
      // Strip pre-release suffixes (e.g., -rc1, -beta1) to ensure RC/beta versions pass the check
      $wooCommerceVersion = $wooCommerceVersion ? preg_replace('/[^0-9.].*/', '', $wooCommerceVersion) : null;
      if ($wooCommerceVersion && version_compare($wooCommerceVersion, self::MIN_WOOCOMMERCE_VERSION_FOR_GENERATED_COUPON_BLOCK, '>=')) {
        $this->patterns = array_merge($this->patterns, [
          new WelcomeWithDiscountEmailPattern($this->cdnAssetUrl),
          new BirthdayEmailPattern($this->cdnAssetUrl, true),
          new WinBackCustomerPattern($this->cdnAssetUrl),
          new AbandonedCartWithDiscountPattern($this->cdnAssetUrl),
          new AskForReviewPostPurchasePattern($this->cdnAssetUrl, 'reward-positive'),
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
       * @param array{name: string, properties: array, email_content?: string} $patternData Pattern name, properties, and optional email content.
       * @param Pattern $pattern The original Pattern object.
       * @return array|null Return modified data or null/false to skip registration.
       */
      $patternData = $this->wp->applyFilters('mailpoet_email_editor_integration_register_pattern', [
        'name' => $patternName,
        'properties' => $patternProperties,
        'email_content' => $pattern->get_email_content(),
      ], $pattern);

      if (
        !is_array($patternData)
        || !isset($patternData['name']) || !is_string($patternData['name'])
        || !isset($patternData['properties']) || !is_array($patternData['properties'])
      ) {
        continue;
      }

      register_block_pattern($patternData['name'], $patternData['properties']); // @phpstan-ignore argument.type (validated as array<string, mixed> just above; register_block_pattern's strict shape is enforced at runtime)

      // Build email content registry: store email_content when it differs from preview content
      $previewContent = isset($patternData['properties']['content']) && is_string($patternData['properties']['content']) ? $patternData['properties']['content'] : '';
      $emailContent = isset($patternData['email_content']) && is_string($patternData['email_content']) ? $patternData['email_content'] : $previewContent;
      if ($emailContent !== $previewContent) {
        $this->emailContentRegistry[$patternData['name']] = $emailContent;
      }
    }

    $this->wp->addFilter(
      'rest_request_after_callbacks',
      [$this, 'addEmailContentToRestResponse'],
      10,
      3
    );
  }

  /**
   * Inject email_content into the block patterns REST API response for patterns
   * that have different content for preview vs insertion.
   *
   * @param \WP_REST_Response|\WP_Error $response
   * @param array $handler
   * @param \WP_REST_Request $request
   * @return \WP_REST_Response|\WP_Error
   */
  public function addEmailContentToRestResponse($response, array $handler, \WP_REST_Request $request) {
    if ($request->get_route() !== '/wp/v2/block-patterns/patterns') {
      return $response;
    }

    if (!($response instanceof \WP_REST_Response)) {
      return $response;
    }

    if (empty($this->emailContentRegistry)) {
      return $response;
    }

    $data = $response->get_data();
    if (!is_array($data)) {
      return $response;
    }

    $modified = false;
    foreach ($data as $index => $pattern) {
      if (!is_array($pattern)) {
        continue;
      }
      $patternName = isset($pattern['name']) && is_string($pattern['name']) ? $pattern['name'] : '';
      if ($patternName !== '' && isset($this->emailContentRegistry[$patternName])) {
        $pattern['email_content'] = $this->emailContentRegistry[$patternName];
        $data[$index] = $pattern;
        $modified = true;
      }
    }

    if ($modified) {
      $response->set_data($data);
    }

    return $response;
  }

  private function registerPatternCategories(): void {
    $categories = [
      [
        'name' => 'sales-announcement',
        'label' => _x('Sales announcements', 'Block pattern category', 'mailpoet'),
        'description' => __('A collection of sales announcement email layouts.', 'mailpoet'),
      ],
      [
        'name' => 'educational-campaign',
        'label' => _x('Educational campaign', 'Block pattern category', 'mailpoet'),
        'description' => __('A collection of educational campaign email layouts.', 'mailpoet'),
      ],
      [
        'name' => 'event',
        'label' => _x('Events', 'Block pattern category', 'mailpoet'),
        'description' => __('A collection of event email layouts.', 'mailpoet'),
      ],
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
      [
        'name' => 'celebrations',
        'label' => _x('Celebrations', 'Block pattern category', 'mailpoet'),
        'description' => __('A collection of celebration email layouts.', 'mailpoet'),
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
      $categories[] = [
        'name' => 'review',
        'label' => _x('Review', 'Block pattern category', 'mailpoet'),
        'description' => __('A collection of review follow-up email layouts.', 'mailpoet'),
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
