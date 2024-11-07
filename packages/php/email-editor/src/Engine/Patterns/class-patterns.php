<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Patterns;

use MailPoet\EmailEditor\Utils\Cdn_Asset_Url;

class Patterns {
  private $namespace = 'mailpoet';
  protected $cdnAssetUrl;

  public function __construct(
    Cdn_Asset_Url $cdnAssetUrl
  ) {
    $this->cdnAssetUrl = $cdnAssetUrl;
  }

  public function initialize(): void {
    $this->registerBlockPatternCategory();
    $this->registerPatterns();
  }

  private function registerBlockPatternCategory() {
    register_block_pattern_category(
      'mailpoet',
      [
        'label' => _x('MailPoet', 'Block pattern category', 'mailpoet'),
        'description' => __('A collection of email template layouts.', 'mailpoet'),
      ]
    );
  }

  private function registerPatterns() {
    $this->registerPattern('default', new Library\Default_Content($this->cdnAssetUrl));
    $this->registerPattern('default-full', new Library\Default_Content_Full($this->cdnAssetUrl));
  }

  private function registerPattern($name, $pattern) {
    register_block_pattern($this->namespace . '/' . $name, $pattern->getProperties());
  }
}
