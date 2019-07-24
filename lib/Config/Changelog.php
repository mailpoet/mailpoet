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
  private $url_helper;

  /** @var MP2Migrator */
  private $mp2_migrator;

  function __construct(
    SettingsController $settings,
    WPFunctions $wp,
    Helper $wooCommerceHelper,
    Url $url_helper,
    MP2Migrator $mp2_migrator
  ) {
    $this->wooCommerceHelper = $wooCommerceHelper;
    $this->settings = $settings;
    $this->wp = $wp;
    $this->url_helper = $url_helper;
    $this->mp2_migrator = $mp2_migrator;
  }

  function init() {
    $doing_ajax = (bool)(defined('DOING_AJAX') && DOING_AJAX);

    // don't run any check when it's an ajax request
    if ($doing_ajax) {
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

  function check() {
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
    if (!in_array($_GET['page'], ['mailpoet-migration', 'mailpoet-settings']) && $this->mp2_migrator->isMigrationStartedAndNotCompleted()) {
      // Force the redirection if the migration has started but is not completed
      return $this->terminateWithRedirect($this->wp->adminUrl('admin.php?page=mailpoet-migration'));
    }

    if ($version === null && $this->mp2_migrator->isMigrationNeeded()) {
       $this->terminateWithRedirect($this->wp->adminUrl('admin.php?page=mailpoet-migration'));
    }
  }

  private function setupNewInstallation() {
    // ensure there was no MP2 migration (migration resets $version so it must be checked)
    if ($this->settings->get(MP2Migrator::MIGRATION_STARTED_SETTING_KEY) === null) {
      $this->settings->set('show_intro', true);
    }
    $this->settings->set('show_congratulate_after_first_newsletter', true);
    $this->settings->set('show_poll_success_delivery_preview', true);
  }

  private function checkWelcomeWizard() {
    $skip_wizard = $this->wp->applyFilters('mailpoet_skip_welcome_wizard', false);
    if (!$skip_wizard) {
      $this->terminateWithRedirect($this->wp->adminUrl('admin.php?page=mailpoet-welcome-wizard'));
    }
  }

  private function checkWooCommerceListImportPage() {
    if ($this->wp->applyFilters('mailpoet_skip_woocommerce_import_page', false)) {
      return;
    }
    if (
      !in_array($_GET['page'], ['mailpoet-revenue-tracking-permission', 'mailpoet-woocommerce-list-import', 'mailpoet-welcome-wizard', 'mailpoet-migration'])
      && !$this->settings->get('woocommerce_import_screen_displayed')
      && $this->wooCommerceHelper->isWooCommerceActive()
      && $this->wooCommerceHelper->getOrdersCount() >= 1
      && $this->wp->currentUserCan('administrator')
    ) {
      $this->url_helper->redirectTo($this->wp->adminUrl('admin.php?page=mailpoet-woocommerce-list-import'));
    }
  }

  private function checkRevenueTrackingPermissionPage() {
    if (
      !in_array($_GET['page'], ['mailpoet-revenue-tracking-permission', 'mailpoet-woocommerce-list-import', 'mailpoet-welcome-wizard', 'mailpoet-migration'])
      && ($this->settings->get('woocommerce.accept_cookie_revenue_tracking.set') === null)
      && $this->settings->get('tracking.enabled')
      && $this->wooCommerceHelper->isWooCommerceActive()
      && $this->wp->currentUserCan('administrator')
    ) {
      $this->url_helper->redirectTo($this->wp->adminUrl('admin.php?page=mailpoet-revenue-tracking-permission'));
    }
  }

  private function terminateWithRedirect($redirect_url) {
    // save version number
    $this->settings->set('version', Env::$version);
    $this->url_helper->redirectWithReferer($redirect_url);
  }
}
