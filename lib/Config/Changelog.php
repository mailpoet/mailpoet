<?php

namespace MailPoet\Config;

use MailPoet\Settings\SettingsController;
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

  /** @var MP2Migrator */
  private $mp2Migrator;

  public function __construct(
    SettingsController $settings,
    WPFunctions $wp,
    Helper $wooCommerceHelper,
    Url $urlHelper,
    MP2Migrator $mp2Migrator
  ) {
    $this->wooCommerceHelper = $wooCommerceHelper;
    $this->settings = $settings;
    $this->wp = $wp;
    $this->urlHelper = $urlHelper;
    $this->mp2Migrator = $mp2Migrator;
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
      (isset($_GET['page']) && strpos($_GET['page'], 'mailpoet') !== 0)
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
    $this->checkMp2Migration($version);
    if ($version === null) {
      $this->setupNewInstallation();
      $this->checkWelcomeWizard();
    }
    $this->checkWooCommerceListImportPage();
    $this->checkRevenueTrackingPermissionPage();
  }

  private function checkMp2Migration($version) {
    if (!in_array($_GET['page'], ['mailpoet-migration', 'mailpoet-settings']) && $this->mp2Migrator->isMigrationStartedAndNotCompleted()) {
      // Force the redirection if the migration has started but is not completed
      return $this->terminateWithRedirect($this->wp->adminUrl('admin.php?page=mailpoet-migration'));
    }

    if ($version === null && $this->mp2Migrator->isMigrationNeeded()) {
       $this->terminateWithRedirect($this->wp->adminUrl('admin.php?page=mailpoet-migration'));
    }
  }

  private function setupNewInstallation() {
    $this->settings->set('show_congratulate_after_first_newsletter', true);
  }

  private function checkWelcomeWizard() {
    $skipWizard = $this->wp->applyFilters('mailpoet_skip_welcome_wizard', false);
    if (!$skipWizard) {
      $this->terminateWithRedirect($this->wp->adminUrl('admin.php?page=mailpoet-welcome-wizard'));
    }
  }

  private function checkWooCommerceListImportPage() {
    if ($this->wp->applyFilters('mailpoet_skip_woocommerce_import_page', false)) {
      return;
    }
    if (
      !in_array($_GET['page'], ['mailpoet-woocommerce-setup', 'mailpoet-welcome-wizard', 'mailpoet-migration'])
      && !$this->settings->get('woocommerce_import_screen_displayed')
      && $this->wooCommerceHelper->isWooCommerceActive()
      && $this->wooCommerceHelper->getOrdersCountCreatedBefore($this->settings->get('installed_at')) > 0
      && $this->wp->currentUserCan('administrator')
    ) {
      $this->urlHelper->redirectTo($this->wp->adminUrl('admin.php?page=mailpoet-woocommerce-setup'));
    }
  }

  private function checkRevenueTrackingPermissionPage() {
    if (
      !in_array($_GET['page'], ['mailpoet-woocommerce-setup', 'mailpoet-welcome-wizard', 'mailpoet-migration'])
      && ($this->settings->get('woocommerce.accept_cookie_revenue_tracking.set') === null)
      && $this->settings->get('tracking.enabled')
      && $this->wooCommerceHelper->isWooCommerceActive()
      && $this->wp->currentUserCan('administrator')
    ) {
      $this->urlHelper->redirectTo($this->wp->adminUrl('admin.php?page=mailpoet-woocommerce-setup'));
    }
  }

  private function terminateWithRedirect($redirectUrl) {
    // save version number
    $this->settings->set('version', Env::$version);
    $this->urlHelper->redirectWithReferer($redirectUrl);
  }
}
