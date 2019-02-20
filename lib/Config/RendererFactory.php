<?php

namespace MailPoet\Config;

if (!defined('ABSPATH')) exit;

class RendererFactory {

  /** @var Renderer */
  private $renderer;

  function getRenderer() {
    if (!$this->renderer) {
      $caching = !WP_DEBUG;
      $debugging = WP_DEBUG;
      $this->renderer = new Renderer($caching, $debugging);
    }
    return $this->renderer;
  }
}
