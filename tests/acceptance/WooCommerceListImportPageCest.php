<?php

namespace MailPoet\Test\Acceptance;

require_once __DIR__ . '/../DataFactories/WooCommerceProduct.php';
require_once __DIR__ . '/../DataFactories/WooCommerceCustomer.php';
require_once __DIR__ . '/../DataFactories/WooCommerceOrder.php';

use MailPoet\Test\DataFactories\WooCommerceCustomer;
use MailPoet\Test\DataFactories\WooCommerceOrder;
use MailPoet\Test\DataFactories\WooCommerceProduct;

class WooCommerceListImportPageCest {

  /** @var WooCommerceProduct */
  private $product_factory;

  /** @var WooCommerceCustomer */
  private $customer_factory;

  /** @var WooCommerceOrder*/
  private $order_factory;

  function _before(\AcceptanceTester $I) {
    $I->activateWooCommerce();
    $this->product_factory = new WooCommerceProduct($I);
    $this->customer_factory = new WooCommerceCustomer($I);
    $this->order_factory = new WooCommerceOrder($I);
    // Cleanup
    $this->customer_factory->deleteAll();
    $this->product_factory->deleteAll();
    $this->order_factory->deleteAll();
  }

  function importPageFormBehaviourTest(\AcceptanceTester $I) {
    $I->login();
    $I->amOnPage('wp-admin/admin.php?page=mailpoet-woocommerce-list-import');
    $I->see('WooCommerce customers now have their own list');
    $unsubscribed_radio = '[data-automation-id="import_as_unsubscribed"]';
    $subscribed_radio = '[data-automation-id="import_as_subscribed"]';
    $submit_button = '[data-automation-id="submit_woo_commerce_list_import"]';
    $I->cantSeeCheckboxIsChecked($unsubscribed_radio);
    $I->cantSeeCheckboxIsChecked($subscribed_radio);
    $I->seeElement("$submit_button:disabled");
    $I->selectOption($unsubscribed_radio, 'unsubscribed');
    $I->canSeeCheckboxIsChecked($unsubscribed_radio);
    $I->seeElement("$submit_button:not(:disabled)");
    $I->seeNoJSErrors();
    $I->click($submit_button);
    $I->seeNoJSErrors();
    $I->seeInCurrentUrl('wp-admin/admin.php?page=mailpoet-newsletters');
  }
}
