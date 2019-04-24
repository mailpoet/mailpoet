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
    $I->waitForElement('[data-automation-id="newsletters_listing_tabs"]');
    $I->seeInCurrentUrl('wp-admin/admin.php?page=mailpoet-newsletters');
  }

  function importListPageRedirectionTest(\AcceptanceTester $I) {
    $settings_factory = new Settings();
    $settings_factory->withWooCommerceListImportPageDisplayed(false);
    $order = $this->order_factory->create();
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->seeInCurrentUrl('wp-admin/admin.php?page=mailpoet-woocommerce-list-import');
    $I->amOnMailpoetPage('Emails');
    $I->seeInCurrentUrl('wp-admin/admin.php?page=mailpoet-woocommerce-list-import');
    $this->order_factory->delete($order['id']);
  }

  function importListPageImportTest(\AcceptanceTester $I) {
    $settings_factory = new Settings();
    $settings_factory->withWooCommerceListImportPageDisplayed(false);
    $order = $this->order_factory->create();
    $guest_user_data = $order['billing'];
    $registered_customer = $this->customer_factory->withEmail('customer1@email.com')->create();
    $I->login();
    $I->amOnPage('wp-admin/admin.php?page=mailpoet-woocommerce-list-import');
    $subscribed_radio = '[data-automation-id="import_as_subscribed"]';
    $submit_button = '[data-automation-id="submit_woo_commerce_list_import"]';
    $I->selectOption($subscribed_radio, 'subscribed');
    $I->click($submit_button);
    $I->seeNoJSErrors();
    $I->waitForElement('[data-automation-id="newsletters_listing_tabs"]');
    $I->seeInCurrentUrl('wp-admin/admin.php?page=mailpoet-newsletters');
    $I->amOnMailpoetPage('Lists');
    $I->waitForText('WooCommerce Customers');
    $I->moveMouseOver('[data-automation-id="segment_name_WooCommerce Customers"]');
    $I->click('[data-automation-id="view_subscribers_WooCommerce Customers"]');
    $I->waitForListingItemsToLoad();
    $I->canSee($registered_customer['email']);
    $I->reloadPage();
    // It takes more time to sync guest user
    // So we reload page several times and check for guest customer email
    for ($i = 0; $i < 15; $i++) {
      try {
        $I->wait(1);
        $I->reloadPage();
        $I->see($guest_user_data['email']);
        return;
      } catch (\PHPUnit_Framework_Exception $e) {
        continue;
      }
    }
    $I->see($guest_user_data['email']);
  }

  function _after(\AcceptanceTester $I) {
    $I->deactivateWooCommerce();
  }
}
