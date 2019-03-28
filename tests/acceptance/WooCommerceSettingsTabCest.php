<?php

namespace MailPoet\Test\Acceptance;

class WooCommerceSettingsTabCest {

  function _before(\AcceptanceTester $I) {
    $I->activateWooCommerce();
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
    $I->dontSee('WooCommerce');
    $I->seeNoJSErrors();
  }

  function _after(\AcceptanceTester $I) {
    $I->deactivateWooCommerce();
  }
}
