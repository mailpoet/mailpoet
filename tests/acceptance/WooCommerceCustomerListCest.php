<?php

namespace MailPoet\Test\Acceptance;

require_once __DIR__ . '/../DataFactories/Settings.php';
require_once __DIR__ . '/../DataFactories/WooCommerceProduct.php';
require_once __DIR__ . '/../DataFactories/WooCommerceCustomer.php';
require_once __DIR__ . '/../DataFactories/WooCommerceOrder.php';

use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\WooCommerceCustomer;
use MailPoet\Test\DataFactories\WooCommerceOrder;
use MailPoet\Test\DataFactories\WooCommerceProduct;

class WooCommerceCustomerListCest {

  /** @var WooCommerceProduct */
  private $product_factory;

  function _before(\AcceptanceTester $I) {
    $I->activateWooCommerce();
    $this->product_factory = new WooCommerceProduct($I);
    $settings_factory = new Settings();
    $settings_factory->withWooCommerceListImportPageDisplayed(true);
    $customer_factory = new WooCommerceCustomer($I);
    $customer_factory->deleteAll();
  }

  function newCustomerIsAddedToListTest(\AcceptanceTester $I) {
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

  function _after(\AcceptanceTester $I) {
    $I->deactivateWooCommerce();
  }
}
