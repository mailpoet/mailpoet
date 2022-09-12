<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Settings;

/**
 * @group woo
 */
class WooCommerceSettingsTabCest {

  const CUSTOMIZE_SELECTOR = '[data-automation-id="mailpoet_woocommerce_customize"]';
  const DISABLE_SELECTOR = '[data-automation-id="mailpoet_woocommerce_disable"]';

  /** @var Settings */
  private $settingsFactory;

  public function _before(\AcceptanceTester $i) {
    $i->activateWooCommerce();
    $this->settingsFactory = new Settings();
    $this->settingsFactory->withCookieRevenueTrackingDisabled();
    $this->settingsFactory->withWooCommerceListImportPageDisplayed(true);
  }

  public function checkWooCommerceTabExists(\AcceptanceTester $i) {
    $i->wantTo('Check WooCommerce settings tab exists when the WooCommerce plugin is active');

    $i->login();
    $i->amOnMailpoetPage('Settings');
    $i->waitForText('WooCommerce');
    $i->click('[data-automation-id="woocommerce_settings_tab"]');
    $i->waitForText('Opt-in on checkout');
    $i->seeNoJSErrors();

    // The tab is hidden when WooCommerce is deactivated
    $i->deactivateWooCommerce();
    $i->amOnMailpoetPage('Settings');
    $i->dontSeeElement('[data-automation-id="woocommerce_settings_tab"]');
    $i->seeNoJSErrors();
  }

  public function checkWooCommercePluginSettingsAreDisabled(\AcceptanceTester $i) {
    $this->settingsFactory->withWooCommerceEmailCustomizerEnabled();

    $i->wantTo('Check WooCommerce plugin email settings are overlayed with link to MailPoet');

    $i->login();
    $i->amOnPage("/wp-admin/admin.php?page=wc-settings&tab=general");
    $i->dontSeeElementInDOM(self::CUSTOMIZE_SELECTOR);

    $i->amOnPage("/wp-admin/admin.php?page=wc-settings&tab=email");
    $i->scrollTo(self::CUSTOMIZE_SELECTOR);
    $href = $i->grabAttributeFrom(self::CUSTOMIZE_SELECTOR, 'href');
    expect($href)->stringContainsString('?page=mailpoet-newsletter-editor&id=');
    $href = $i->grabAttributeFrom(self::DISABLE_SELECTOR, 'href');
    expect($href)->stringContainsString('?page=mailpoet-settings#woocommerce');

    $i->amOnPage("/wp-admin/admin.php?page=wc-settings&tab=email&section=wc_email_new_order");
    $i->dontSeeElementInDOM(self::CUSTOMIZE_SELECTOR);
  }
}
