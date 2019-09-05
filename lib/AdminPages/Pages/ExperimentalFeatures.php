<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;

class ExperimentalFeatures {
  /** @var PageRenderer */
  private $page_renderer;

  function __construct(PageRenderer $page_renderer) {
    $this->page_renderer = $page_renderer;
  }

  function render() {
    $this->page_renderer->displayPage('experimental-features.html', []);
  }
}
