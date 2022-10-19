<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\Menu;
use MailPoet\Settings\SettingsController;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;

class WelcomeWizard {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  /** @var WooCommerceHelper */
  private $wooCommerceHelper;

  public function __construct(
    PageRenderer $pageRenderer,
    SettingsController $settings,
    WooCommerceHelper $wooCommerceHelper,
    WPFunctions $wp
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->settings = $settings;
    $this->wooCommerceHelper = $wooCommerceHelper;
    $this->wp = $wp;
  }

  public function render() {
    if ((bool)(defined('DOING_AJAX') && DOING_AJAX)) return;
    $data = [
      'finish_wizard_url' => $this->wp->adminUrl('admin.php?page=' . Menu::MAIN_PAGE_SLUG),
      'sender' => $this->settings->get('sender'),
      'admin_email' => $this->wp->getOption('admin_email'),
      'current_wp_user' => $this->wp->wpGetCurrentUser()->to_array(),
      'show_customers_import' => $this->wooCommerceHelper->getCustomersCount() > 0,
    ];
    $this->pageRenderer->displayPage('welcome_wizard.html', $data);
  }
}
