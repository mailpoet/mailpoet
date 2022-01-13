<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\Menu;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class WooCommerceSetup {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  public function __construct(
    PageRenderer $pageRenderer,
    WPFunctions $wp,
    SettingsController $settings
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->wp = $wp;
    $this->settings = $settings;
  }

  public function render() {
    if ((bool)(defined('DOING_AJAX') && DOING_AJAX)) return;
    $data = [
      'finish_wizard_url' => $this->wp->adminUrl('admin.php?page=' . Menu::MAIN_PAGE_SLUG),
      'settings' => $this->settings->getAll(),
    ];
    $this->pageRenderer->displayPage('woocommerce_setup.html', $data);
  }
}
