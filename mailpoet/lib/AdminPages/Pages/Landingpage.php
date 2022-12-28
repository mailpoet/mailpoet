<?php declare(strict_types = 1);

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\Menu;
use MailPoet\WP\Functions as WPFunctions;

class Landingpage {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    PageRenderer $pageRenderer,
    WPFunctions $wp
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->wp = $wp;
  }

  public function render() {
    $data = [
      'welcome_wizard_url' => $this->wp->adminUrl('admin.php?page=' . Menu::WELCOME_WIZARD_PAGE_SLUG),
    ];
    $this->pageRenderer->displayPage('landingpage.html', $data);
  }
}
