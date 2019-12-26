<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\ScheduledTask;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\WooCommerceCustomer;
use MailPoet\Test\DataFactories\WooCommerceOrder;

class WooCommerceListImportPageCest {

  /** @var WooCommerceCustomer */
  private $customer_factory;

  /** @var Settings */
  private $settings;

  /** @var WooCommerceOrder*/
  private $order_factory;

  protected function _inject(Settings $settings) {
    $this->settings = $settings;
  }

  public function _before(\AcceptanceTester $I) {
    $I->activateWooCommerce();
    $this->customer_factory = new WooCommerceCustomer($I);
    $this->order_factory = new WooCommerceOrder($I);
  }

  public function importListPageImportTest(\AcceptanceTester $I) {
    $this->settings
      ->withWooCommerceListImportPageDisplayed(false)
      ->withCronTriggerMethod('WordPress');
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
    $I->waitForElement('[data-automation-id="create_standard"]');
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
        $I->wait(2);
        $I->reloadPage();
        $I->see($guest_user_data['email']);
        return;
      } catch (\PHPUnit_Framework_Exception $e) {
        continue;
      }
    }
    $I->see($guest_user_data['email']);
  }

  public function importPageFormBehaviourTest(\AcceptanceTester $I) {
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
    $I->waitForElement('[data-automation-id="create_standard"]');
  }

  /**
   * Test that admin is always redirected to WooCommerce list import page and
   * can't go to another page unless he submits the form
   * @param \AcceptanceTester $I
   */
  public function importListPageRedirectionTest(\AcceptanceTester $I) {
    $this->settings->withWooCommerceListImportPageDisplayed(false);
    $order = $this->order_factory
      ->withDateCreated('2001-08-22T11:11:56') // any time in the past. Must be before the plugin activation
      ->create();
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->seeInCurrentUrl('wp-admin/admin.php?page=mailpoet-woocommerce-list-import');
    $I->amOnMailpoetPage('Emails');
    $I->seeInCurrentUrl('wp-admin/admin.php?page=mailpoet-woocommerce-list-import');
    $this->order_factory->delete($order['id']);
  }
}
