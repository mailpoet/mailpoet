<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns;

use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\AbandonedCartPattern;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\NewsletterPattern;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\WelcomeEmailPattern;
use MailPoet\Util\CdnAssetUrl;

class PatternsController {
  private CdnAssetUrl $cdnAssetUrl;

  public function __construct(
    CdnAssetUrl $cdnAssetUrl
  ) {
    $this->cdnAssetUrl = $cdnAssetUrl;
  }

  public function registerPatterns(): void {
    $this->registerPatternCategories();

    $patterns = [];
    $patterns[] = new NewsletterPattern($this->cdnAssetUrl);
    $patterns[] = new WelcomeEmailPattern($this->cdnAssetUrl);
    $patterns[] = new AbandonedCartPattern($this->cdnAssetUrl);
    foreach ($patterns as $pattern) {
      register_block_pattern($pattern->get_namespace() . '/' . $pattern->get_name(), $pattern->get_properties());
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
