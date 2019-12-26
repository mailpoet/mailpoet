<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\WooCommerceProduct;

class WooCommerceCheckoutOptinCest {

  /** @var Settings */
  private $settings_factory;

  /** @var WooCommerceProduct */
  private $product_factory;

  public function _before(\AcceptanceTester $I) {
    $I->activateWooCommerce();
    $this->product_factory = new WooCommerceProduct($I);
    $this->settings_factory = new Settings();
    $this->settings_factory->withWooCommerceListImportPageDisplayed(true);
    $this->settings_factory->withCookieRevenueTrackingDisabled();
    $this->settings_factory->withWooCommerceCheckoutOptinEnabled();
  }

  public function checkoutWithOptinCheckboxChecked(\AcceptanceTester $I) {
    $customer_email = 'wc_customer_checked@example.com';
    $product = $this->product_factory->create();
    $I->orderProduct($product, $customer_email);

    $I->login();
    $I->amOnMailpoetPage('Subscribers');
    $I->searchFor($customer_email);
    $I->waitForListingItemsToLoad();
    $I->waitForText($customer_email);
    // Customer is subscribed to the WC customers list
    $I->see('WooCommerce Customers', 'td[data-colname="Lists"]');
  }

  public function checkoutWithOptinCheckboxUnchecked(\AcceptanceTester $I) {
    $customer_email = 'wc_customer_unchecked@example.com';
    $product = $this->product_factory->create();
    $I->orderProduct($product, $customer_email, true, false);

    $I->login();
    $I->amOnMailpoetPage('Subscribers');
    $I->searchFor($customer_email);
    $I->waitForListingItemsToLoad();
    $I->waitForText($customer_email);
    // Customer is unsubscribed from the WC customers list
    $I->dontSee('WooCommerce Customers', 'td[data-colname="Lists"]');
  }
}
