<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags;

use MailPoet\WP\Functions as WPFunctions;

class Site {

  private WPFunctions $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function getTitle(array $context, array $args = []): string {
    return htmlspecialchars_decode($this->wp->getBloginfo('name'));
  }

  public function getHomepageURL(array $context, array $args = []): string {
    return $this->wp->getBloginfo('url');
  }
}
