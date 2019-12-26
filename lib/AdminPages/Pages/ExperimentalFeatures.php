<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;

class ExperimentalFeatures {
  /** @var PageRenderer */
  private $page_renderer;

  public function __construct(PageRenderer $page_renderer) {
    $this->page_renderer = $page_renderer;
  }

  public function render() {
    $this->page_renderer->displayPage('experimental-features.html', []);
  }
}
