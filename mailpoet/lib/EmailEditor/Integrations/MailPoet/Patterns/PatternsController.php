<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns;

use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\DefaultContent;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\DefaultContentFull;
use MailPoet\Util\CdnAssetUrl;
use MailPoet\WP\Functions as WPFunctions;

class PatternsController {
  private CdnAssetUrl $cdnAssetUrl;
  private WPFunctions $wp;

  public function __construct(
    CdnAssetUrl $cdnAssetUrl,
    WPFunctions $wpFunctions
  ) {
    $this->cdnAssetUrl = $cdnAssetUrl;
    $this->wp = $wpFunctions;
  }

  public function initialize(): void {
    $this->wp->addFilter('mailpoet_block_pattern_categories', [$this, 'registerCategories']);
    $this->wp->addFilter('mailpoet_block_patterns', [$this, 'registerPatterns']);
  }

  public function registerCategories(array $categories): array {
    $categories[] = [
      'name' => 'mailpoet',
      'label' => _x('MailPoet', 'Block pattern category', 'mailpoet'),
      'description' => __('A collection of email template layouts.', 'mailpoet'),
    ];
    return $categories;
  }

  public function registerPatterns($patterns): array {
    $patterns[] = new DefaultContentFull($this->cdnAssetUrl);
    $patterns[] = new DefaultContent($this->cdnAssetUrl);
    return $patterns;
  }
}
