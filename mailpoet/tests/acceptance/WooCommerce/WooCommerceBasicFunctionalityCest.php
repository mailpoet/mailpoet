<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\WooCommerceCustomer;
use MailPoet\Test\DataFactories\WooCommerceOrder;
use MailPoet\Test\DataFactories\WooCommerceProduct;

/**
 * @group woo
 * @group frontend
 */
class WooCommerceBasicFunctionalityCest {
  private Settings $settingsFactory;

  public function _before(\AcceptanceTester $i) {
    $i->login();
    $i->activateWooCommerce();
    $this->settingsFactory = new Settings();
    $this->settingsFactory->withWooCommerceListImportPageDisplayed(true);
    $this->settingsFactory->withCookieRevenueTrackingDisabled();
  }

  public function testWooCommercePluginWorks(\AcceptanceTester $i) {
    $i->wantTo('Verify that WooCommerce plugin is working properly');

    $i->wantTo('Check WooCommerce admin menu is present');
    $i->amOnPage('/wp-admin');
    $i->see('WooCommerce');

    $i->wantTo('Navigate to WooCommerce products page');
    $i->amOnPage('/wp-admin/edit.php?post_type=product');
    $i->waitForText('Products', 10);

    $i->wantTo('Check that WooCommerce is properly activated');
    $i->amOnPage('/wp-admin/plugins.php');
    $i->seeElement('tr[data-slug="woocommerce"]');
    $i->see('Deactivate', 'tr[data-slug="woocommerce"] .row-actions .deactivate');
  }

  public function testCustomerCanCreateOrder(\AcceptanceTester $i) {
    $i->wantTo('Verify that a customer can create a new order');

    $i->wantTo('Create a test product using WooCommerce data factory');
    $productFactory = new WooCommerceProduct($i);
    $product = $productFactory
      ->withName('Test Product for Order')
      ->withPrice(2999)
      ->create();

    $i->wantTo('Create a test customer using WooCommerce data factory');
    $customerFactory = new WooCommerceCustomer($i);
    $customer = $customerFactory
      ->withFirstName('Test')
      ->withLastName('Customer')
      ->withEmail('test.customer@example.com')
      ->create();

    $i->wantTo('Create an order using WooCommerce data factory');
    $orderFactory = new WooCommerceOrder($i);
    $order = $orderFactory
      ->withCustomer($customer)
      ->withProducts([$product])
      ->create();

    $i->wantTo('Verify the order was created successfully');
    // Order creation successful if we reach this point without errors

    $i->wantTo('Test basic WooCommerce functionality');
    $i->wantTo('Check that the product was created');
    $i->amOnPage('/wp-admin/edit.php?post_type=product');
    $i->waitForText('Products', 10);
    $i->see($product['name']);

    $i->wantTo('Check that the order was created');
    $i->amOnPage('/wp-admin/edit.php?post_type=shop_order');
    $i->waitForText('Orders', 10);
    $i->see('#' . $order['id']);

    $i->wantTo('Verify WooCommerce is working by checking for basic elements');
    $i->amOnPage('/wp-admin/admin.php?page=wc-admin');
    $i->waitForText('WooCommerce', 10);
  }

  public function testWooCommerceIntegrationWithMailPoet(\AcceptanceTester $i) {
    $i->wantTo('Verify WooCommerce integration with MailPoet');

    $i->wantTo('Check that WooCommerce marketing integration is working');
    $i->amOnPage('/wp-admin/admin.php?page=wc-admin&path=%2Fmarketing');
    $i->waitForText('Channels', 10);
    $i->see('MailPoet');

    $i->wantTo('Check MailPoet WooCommerce settings');
    $i->amOnMailPoetPage('Settings');
    $i->see('WooCommerce');
  }
}
