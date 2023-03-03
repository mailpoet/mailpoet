<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\Menu;
use MailPoet\Config\ServicesChecker;
use MailPoet\Settings\SettingsController;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;

class WelcomeWizard {
  const TRACK_LOADDED_VIA_WOOCOMMERCE_SETTING_NAME = 'send_event_that_wizard_was_loaded_via_woocommerce';

  /** @var PageRenderer */
  private $pageRenderer;

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  /** @var WooCommerceHelper */
  private $wooCommerceHelper;

  /** @var ServicesChecker */
  private $servicesChecker;

  public function __construct(
    PageRenderer $pageRenderer,
    SettingsController $settings,
    WooCommerceHelper $wooCommerceHelper,
    WPFunctions $wp,
    ServicesChecker $servicesChecker
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->settings = $settings;
    $this->wooCommerceHelper = $wooCommerceHelper;
    $this->wp = $wp;
    $this->servicesChecker = $servicesChecker;
  }

  public function render() {
    if ((bool)(defined('DOING_AJAX') && DOING_AJAX)) return;

    $loadedViaWooCommerce = $this->settings->get(WelcomeWizard::TRACK_LOADDED_VIA_WOOCOMMERCE_SETTING_NAME, false);

    if (!$loadedViaWooCommerce && isset($_GET['mailpoet_wizard_loaded_via_woocommerce'])) {
      // This setting is used to send an event to Mixpanel in another request as, before completing the wizard, Mixpanel is not enabled.
      $this->settings->set(WelcomeWizard::TRACK_LOADDED_VIA_WOOCOMMERCE_SETTING_NAME, 1);
    }

    $settings = $this->settings->getAll();
    $premiumKeyValid = $this->servicesChecker->isPremiumKeyValid(false);
    // force MSS key check even if the method isn't active
    $mpApiKeyValid = $this->servicesChecker->isMailPoetAPIKeyValid(false, true);

    $data = [
      'finish_wizard_url' => $this->wp->adminUrl('admin.php?page=' . Menu::MAIN_PAGE_SLUG),
      'admin_email' => $this->wp->getOption('admin_email'),
      'current_wp_user' => $this->wp->wpGetCurrentUser()->to_array(),
      'show_customers_import' => $this->wooCommerceHelper->getCustomersCount() > 0,
      'settings' => $settings,
      'premium_key_valid' => !empty($premiumKeyValid),
      'mss_key_valid' => !empty($mpApiKeyValid),
    ];
    $this->pageRenderer->displayPage('welcome_wizard.html', $data);
  }
}
