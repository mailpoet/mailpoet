<?php

namespace MailPoet\Config;

use MailPoet\WP\Functions as WPFunctions;

class RendererFactory {

  /** @var Renderer|null */
  private $renderer;

  public function getRenderer() {
    if (!$this->renderer) {
      $caching = WPFunctions::get()->applyFilters('mailpoet_template_cache_enabled', !WP_DEBUG);
      $debugging = WP_DEBUG;
      $this->renderer = new Renderer($caching, $debugging);
    }
    return $this->renderer;
  }
}
