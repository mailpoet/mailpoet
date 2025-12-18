<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns;

use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\AbandonedCartPattern;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\NewsletterPattern;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\WelcomeEmailPattern;
use MailPoet\Util\CdnAssetUrl;
use MailPoet\WP\Functions as WPFunctions;

class PatternsController {
  private CdnAssetUrl $cdnAssetUrl;
  private WPFunctions $wp;

  public function __construct(
    CdnAssetUrl $cdnAssetUrl,
    WPFunctions $wp
  ) {
    $this->cdnAssetUrl = $cdnAssetUrl;
    $this->wp = $wp;
  }

  public function registerPatterns(): void {
    $this->registerPatternCategories();

    $patterns = [];
    $patterns[] = new NewsletterPattern($this->cdnAssetUrl);
    $patterns[] = new WelcomeEmailPattern($this->cdnAssetUrl);
    $patterns[] = new AbandonedCartPattern($this->cdnAssetUrl);

    foreach ($patterns as $pattern) {
      $patternName = $pattern->get_namespace() . '/' . $pattern->get_name();
      $patternProperties = $pattern->get_properties();

      /**
       * Filters pattern data before it is registered as a block pattern.
       *
       * @param array{name: string, properties: array} $patternData Pattern name and properties.
       * @param Pattern $pattern The original Pattern object.
       * @return array|null Return modified data or null/false to skip registration.
       */
      $patternData = $this->wp->applyFilters('mailpoet_email_editor_register_pattern', [
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
      [
        'name' => 'abandoned-cart',
        'label' => _x('Abandoned cart', 'Block pattern category', 'mailpoet'),
        'description' => __('A collection of abandoned cart email layouts.', 'mailpoet'),
      ],
    ];

    foreach ($categories as $category) {
      register_block_pattern_category($category['name'], [
        'label' => $category['label'],
        'description' => $category['description'],
      ]);
    }
  }
}
