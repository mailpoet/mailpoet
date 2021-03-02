<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;

class Logs {
  /** @var PageRenderer */
  private $pageRenderer;

  public function __construct(PageRenderer $pageRenderer) {
    $this->pageRenderer = $pageRenderer;
  }

  public function render() {
    $this->pageRenderer->displayPage('logs.html', []);
  }
}
