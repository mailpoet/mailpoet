<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Config;

use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Util\Url;
use MailPoet\WooCommerce\Helper;
use MailPoet\WP\Functions as WPFunctions;

class Changelog {
  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  /** @var Helper */
  private $wooCommerceHelper;

  /** @var Url */
  private $urlHelper;

  /** @var TrackingConfig */
  private $trackingConfig;

  public function __construct(
    SettingsController $settings,
    WPFunctions $wp,
    Helper $wooCommerceHelper,
    Url $urlHelper,
    TrackingConfig $trackingConfig
  ) {
    $this->wooCommerceHelper = $wooCommerceHelper;
    $this->settings = $settings;
    $this->wp = $wp;
    $this->urlHelper = $urlHelper;
    $this->trackingConfig = $trackingConfig;
  }

  public function init() {
    $doingAjax = (bool)(defined('DOING_AJAX') && DOING_AJAX);

    // don't run any check when it's an ajax request
    if ($doingAjax) {
      return;
    }

    // don't run any check when we're not on our pages
    if (
      !(isset($_GET['page']))
      or
      (isset($_GET['page']) && strpos(
        sanitize_text_field(wp_unslash($_GET['page'])),
        'mailpoet'
      ) !== 0)
    ) {
      return;
    }

    WPFunctions::get()->addAction(
      'admin_init',
      [$this, 'check']
    );
  }

  public function check() {
    $version = $this->settings->get('version');
    if ($version === null) {
      $this->setupNewInstallation();
      $this->maybeRedirectToWelcomeWizard();
    }
    $this->checkWooCommerceListImportPage();
    $this->checkRevenueTrackingPermissionPage();
  }

  public function shouldShowWelcomeWizard() {
    if ($this->wp->applyFilters('mailpoet_skip_welcome_wizard', false)) {
      return false;
    }
    return $this->settings->get('version') === null;
  }

  public function shouldShowWooCommerceListImportPage() {
    if ($this->wp->applyFilters('mailpoet_skip_woocommerce_import_page', false)) {
      return false;
    }
    return !$this->settings->get('woocommerce_import_screen_displayed')
      && $this->wooCommerceHelper->isWooCommerceActive()
      && $this->wooCommerceHelper->getOrdersCountCreatedBefore($this->settings->get('installed_at')) > 0
      && $this->wp->currentUserCan('administrator');
  }

  public function shouldShowRevenueTrackingPermissionPage() {
    return ($this->settings->get('woocommerce.accept_cookie_revenue_tracking.set') === null)
      && $this->trackingConfig->isEmailTrackingEnabled()
      && $this->wooCommerceHelper->isWooCommerceActive()
      && $this->wp->currentUserCan('administrator');
  }

  private function setupNewInstallation() {
    $this->settings->set('show_congratulate_after_first_newsletter', true);
  }

  private function maybeRedirectToWelcomeWizard() {
    if ($this->shouldShowWelcomeWizard() && !$this->isWelcomeWizardPage()) {
      $this->urlHelper->redirectWithReferer(
        $this->wp->adminUrl('admin.php?page=mailpoet-welcome-wizard')
      );
    }
  }

  private function isWelcomeWizardPage() {
    return isset($_GET['page']) && $_GET['page'] === Menu::WELCOME_WIZARD_PAGE_SLUG;
  }

  private function checkWooCommerceListImportPage() {
    if (
      !isset($_GET['page']) ||
      !in_array(
        sanitize_text_field(wp_unslash($_GET['page'])),
        [
          'mailpoet-woocommerce-setup',
          'mailpoet-welcome-wizard',
          'mailpoet-migration',
        ]
      )
      && $this->shouldShowWooCommerceListImportPage()
    ) {
      $this->urlHelper->redirectTo($this->wp->adminUrl('admin.php?page=mailpoet-woocommerce-setup'));
    }
  }

  private function checkRevenueTrackingPermissionPage() {
    if (
      !isset($_GET['page']) ||
      !in_array(
        sanitize_text_field(wp_unslash($_GET['page'])),
        [
          'mailpoet-woocommerce-setup',
          'mailpoet-welcome-wizard',
          'mailpoet-migration',
        ]
      )
      && $this->shouldShowRevenueTrackingPermissionPage()
    ) {
      $this->urlHelper->redirectTo($this->wp->adminUrl('admin.php?page=mailpoet-woocommerce-setup'));
    }
  }
}
