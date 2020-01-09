<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;

class ExperimentalFeatures {
  /** @var PageRenderer */
  private $page_renderer;

  public function __construct(PageRenderer $pageRenderer) {
    $this->pageRenderer = $pageRenderer;
  }

  public function render() {
    $this->pageRenderer->displayPage('experimental-features.html', []);
  }
}
