<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\WooCommerceProduct;

class Settings_WooCommerceCheckoutOptinCest {

  /** @var Settings */
  private $settingsFactory;

  /** @var WooCommerceProduct */
  private $productFactory;

  public function _before(\AcceptanceTester $i) {
    $i->activateWooCommerce();
    $this->productFactory = new WooCommerceProduct($i);
    $this->settingsFactory = new Settings();
    $this->settingsFactory->withWooCommerceListImportPageDisplayed(true);
    $this->settingsFactory->withCookieRevenueTrackingDisabled();
    $this->settingsFactory->withWooCommerceCheckoutOptinEnabled();
  }

  public function checkoutWithOptinCheckboxChecked(\AcceptanceTester $i) {
    $customerEmail = 'wc_customer_checked@example.com';
    $product = $this->productFactory->create();
    $i->orderProduct($product, $customerEmail);

    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->searchFor($customerEmail);
    $i->waitForListingItemsToLoad();
    $i->waitForText($customerEmail);
    // Customer is subscribed to the WC customers list
    $i->see('WooCommerce Customers', 'td[data-colname="Lists"]');
  }

  public function checkoutWithOptinCheckboxUnchecked(\AcceptanceTester $i) {
    $customerEmail = 'wc_customer_unchecked@example.com';
    $product = $this->productFactory->create();
    $i->orderProduct($product, $customerEmail, true, false);

    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->searchFor($customerEmail);
    $i->waitForListingItemsToLoad();
    $i->waitForText($customerEmail);
    // Customer is unsubscribed from the WC customers list
    $i->dontSee('WooCommerce Customers', 'td[data-colname="Lists"]');
  }
}
