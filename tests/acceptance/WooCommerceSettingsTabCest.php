<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Settings;

class WooCommerceSettingsTabCest {

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

  function _after(\AcceptanceTester $I) {
    $I->deactivateWooCommerce();
  }
}
