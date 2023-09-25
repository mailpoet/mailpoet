<?php declare(strict_types = 1);

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;

class EmailEditor {
  /** @var PageRenderer */
  private $pageRenderer;

  public function __construct(
    PageRenderer $pageRenderer
  ) {
    $this->pageRenderer = $pageRenderer;
  }

  public function render() {
    $this->pageRenderer->displayPage('email_editor.html', []);
  }
}
