<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Features\FeaturesController;
use MailPoet\Test\DataFactories\Features;
use MailPoet\Test\DataFactories\Settings;

class WooCommerceSettingsTabCest {

  const CUSTOMIZE_SELECTOR = '[data-automation-id="mailpoet_woocommerce_customize"]';
  const DISABLE_SELECTOR = '[data-automation-id="mailpoet_woocommerce_disable"]';

  /** @var Features */
  private $features;

  /** @var Settings */
  private $settings_factory;

  protected function _inject(Features $features) {
    $this->features = $features;
  }

  function _before(\AcceptanceTester $I) {
    $I->activateWooCommerce();
    $this->settings_factory = new Settings();
    $this->settings_factory->withCookieRevenueTrackingDisabled();
    $this->settings_factory->withWooCommerceListImportPageDisplayed(true);
  }

  function checkWooCommerceTabExists(\AcceptanceTester $I) {
    $I->wantTo('Check WooCommerce settings tab exists when the WooCommerce plugin is active');

    $I->login();
    $I->amOnMailpoetPage('Settings');
    $I->waitForText('WooCommerce');
    $I->click('[data-automation-id="woocommerce_settings_tab"]');
    $I->waitForText('Opt-in on checkout');
    $I->seeNoJSErrors();

    // The tab is hidden when WooCommerce is deactivated
    $I->deactivateWooCommerce();
    $I->amOnMailpoetPage('Settings');
    $I->dontSeeElement('[data-automation-id="woocommerce_settings_tab"]');
    $I->seeNoJSErrors();
  }

  function checkWooCommercePluginSettingsAreDisabled(\AcceptanceTester $I) {
    $this->features->withFeatureEnabled(FeaturesController::WC_TRANSACTIONAL_EMAILS_CUSTOMIZER);
    $this->settings_factory->withWooCommerceEmailCustomizerEnabled();

    $I->wantTo('Check WooCommerce plugin email settings are overlayed with link to MailPoet');

    $I->login();
    $I->amOnPage("/wp-admin/admin.php?page=wc-settings&tab=general");
    $I->dontSeeElementInDOM(self::CUSTOMIZE_SELECTOR);

    $I->amOnPage("/wp-admin/admin.php?page=wc-settings&tab=email");
    $I->scrollTo(self::CUSTOMIZE_SELECTOR);
    $href = $I->grabAttributeFrom(self::CUSTOMIZE_SELECTOR, 'href');
    expect($href)->contains('?page=mailpoet-newsletter-editor&id=');
    $href = $I->grabAttributeFrom(self::DISABLE_SELECTOR, 'href');
    expect($href)->contains('?page=mailpoet-settings#woocommerce');

    $I->amOnPage("/wp-admin/admin.php?page=wc-settings&tab=email&section=wc_email_new_order");
    $I->dontSeeElementInDOM(self::CUSTOMIZE_SELECTOR);
  }
}
