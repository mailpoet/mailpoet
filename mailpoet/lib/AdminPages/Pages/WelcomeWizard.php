<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\Menu;
use MailPoet\Features\FeaturesController;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;

class WelcomeWizard {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var SettingsController */
  private $settings;

  /** @var WooCommerceHelper */
  private $woocommerceHelper;

  /** @var WPFunctions */
  private $wp;

  /** @var FeaturesController */
  private $featuresController;

  /** @var SubscribersFeature */
  private $subscribersFeature;

  public function __construct(
    PageRenderer $pageRenderer,
    SettingsController $settings,
    WooCommerceHelper $woocommerceHelper,
    WPFunctions $wp,
    FeaturesController $featuresController,
    SubscribersFeature $subscribersFeature
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->settings = $settings;
    $this->woocommerceHelper = $woocommerceHelper;
    $this->wp = $wp;
    $this->featuresController = $featuresController;
    $this->subscribersFeature = $subscribersFeature;
  }

  public function render() {
    if ((bool)(defined('DOING_AJAX') && DOING_AJAX)) return;
    $data = [
      'is_woocommerce_active' => $this->woocommerceHelper->isWooCommerceActive(),
      'finish_wizard_url' => $this->wp->adminUrl('admin.php?page=' . Menu::MAIN_PAGE_SLUG),
      'sender' => $this->settings->get('sender'),
      'admin_email' => $this->wp->getOption('admin_email'),
      'current_wp_user' => $this->wp->wpGetCurrentUser()->to_array(),
      'subscriber_count' => $this->subscribersFeature->getSubscribersCount(),
      'has_mss_key_specified' => Bridge::isMSSKeySpecified(),
      'settings' => $this->settings->getAll(),
    ];
    $data['mailpoet_feature_flags'] = $this->featuresController->getAllFlags();
    $data['current_wp_user_email'] = $this->wp->wpGetCurrentUser()->user_email;
    $this->pageRenderer->displayPage('welcome_wizard.html', $data);
  }
}
