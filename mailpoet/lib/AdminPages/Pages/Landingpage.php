<?php declare(strict_types = 1);

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;

class Landingpage {
  /** @var PageRenderer */
  private $pageRenderer;

  public function __construct(
    PageRenderer $pageRenderer
  ) {
    $this->pageRenderer = $pageRenderer;
  }

  public function render() {
    $this->pageRenderer->displayPage('landingpage.html');
  }
}
