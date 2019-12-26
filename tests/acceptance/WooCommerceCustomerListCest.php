<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\WooCommerceCustomer;
use MailPoet\Test\DataFactories\WooCommerceProduct;

class WooCommerceCustomerListCest {

  /** @var WooCommerceProduct */
  private $product_factory;

  public function _before(\AcceptanceTester $I) {
    $I->activateWooCommerce();
    $this->product_factory = new WooCommerceProduct($I);
    $settings_factory = new Settings();
    $settings_factory->withWooCommerceListImportPageDisplayed(true);
    $settings_factory->withWooCommerceCheckoutOptinEnabled();
    $settings_factory->withCookieRevenueTrackingDisabled();
  }

  public function newCustomerIsAddedToListTest(\AcceptanceTester $I) {
    $customer_email = 'wc_customer@example.com';
    $product = $this->product_factory->create();
    $I->orderProduct($product, $customer_email);
    $I->login();
    $I->amOnMailpoetPage('Lists');
    $I->waitForText('WooCommerce Customers');
    $I->moveMouseOver('[data-automation-id="segment_name_WooCommerce Customers"]');
    $I->click('[data-automation-id="view_subscribers_WooCommerce Customers"]');
    $I->waitForListingItemsToLoad();
    $I->waitForText($customer_email);
  }
}
