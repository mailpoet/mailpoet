<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\WooCommerceProduct;

class Settings_WooCommerceCustomerListCest {

  /** @var WooCommerceProduct */
  private $productFactory;

  public function _before(\AcceptanceTester $i) {
    $i->activateWooCommerce();
    $this->productFactory = new WooCommerceProduct($i);
    $settingsFactory = new Settings();
    $settingsFactory->withWooCommerceListImportPageDisplayed(true);
    $settingsFactory->withWooCommerceCheckoutOptinEnabled();
    $settingsFactory->withCookieRevenueTrackingDisabled();
  }

  public function newCustomerIsAddedToListTest(\AcceptanceTester $i) {
    $customerEmail = 'wc_customer@example.com';
    $product = $this->productFactory->create();
    $i->orderProduct($product, $customerEmail);
    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->waitForText('WooCommerce Customers');
    $i->moveMouseOver('[data-automation-id="segment_name_WooCommerce Customers"]');
    $i->click('[data-automation-id="view_subscribers_WooCommerce Customers"]');
    $i->waitForListingItemsToLoad();
    $i->waitForText($customerEmail);
  }
}
