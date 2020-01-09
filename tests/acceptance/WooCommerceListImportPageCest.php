<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\ScheduledTask;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\WooCommerceCustomer;
use MailPoet\Test\DataFactories\WooCommerceOrder;

class WooCommerceListImportPageCest {

  /** @var WooCommerceCustomer */
  private $customerFactory;

  /** @var Settings */
  private $settings;

  /** @var WooCommerceOrder*/
  private $orderFactory;

  protected function _inject(Settings $settings) {
    $this->settings = $settings;
  }

  public function _before(\AcceptanceTester $i) {
    $i->activateWooCommerce();
    $this->customerFactory = new WooCommerceCustomer($i);
    $this->orderFactory = new WooCommerceOrder($i);
  }

  public function importListPageImportTest(\AcceptanceTester $i) {
    $this->settings
      ->withWooCommerceListImportPageDisplayed(false)
      ->withCronTriggerMethod('WordPress');
    $order = $this->orderFactory->create();
    $guestUserData = $order['billing'];
    $registeredCustomer = $this->customerFactory->withEmail('customer1@email.com')->create();
    $i->login();
    $i->amOnPage('wp-admin/admin.php?page=mailpoet-woocommerce-list-import');
    $subscribedRadio = '[data-automation-id="import_as_subscribed"]';
    $submitButton = '[data-automation-id="submit_woo_commerce_list_import"]';
    $i->selectOption($subscribedRadio, 'subscribed');
    $i->click($submitButton);
    $i->seeNoJSErrors();
    $i->waitForElement('[data-automation-id="create_standard"]');
    $i->amOnMailpoetPage('Lists');
    $i->waitForText('WooCommerce Customers');
    $i->moveMouseOver('[data-automation-id="segment_name_WooCommerce Customers"]');
    $i->click('[data-automation-id="view_subscribers_WooCommerce Customers"]');
    $i->waitForListingItemsToLoad();
    $i->canSee($registeredCustomer['email']);
    $i->reloadPage();
    // It takes more time to sync guest user
    // So we reload page several times and check for guest customer email
    for ($i = 0; $i < 15; $i++) {
      try {
        $i->wait(2);
        $i->reloadPage();
        $i->see($guestUserData['email']);
        return;
      } catch (\PHPUnit_Framework_Exception $e) {
        continue;
      }
    }
    $i->see($guestUserData['email']);
  }

  public function importPageFormBehaviourTest(\AcceptanceTester $i) {
    $i->login();
    $i->amOnPage('wp-admin/admin.php?page=mailpoet-woocommerce-list-import');
    $i->see('WooCommerce customers now have their own list');
    $unsubscribedRadio = '[data-automation-id="import_as_unsubscribed"]';
    $subscribedRadio = '[data-automation-id="import_as_subscribed"]';
    $submitButton = '[data-automation-id="submit_woo_commerce_list_import"]';
    $i->cantSeeCheckboxIsChecked($unsubscribedRadio);
    $i->cantSeeCheckboxIsChecked($subscribedRadio);
    $i->seeElement("$submitButton:disabled");
    $i->selectOption($unsubscribedRadio, 'unsubscribed');
    $i->canSeeCheckboxIsChecked($unsubscribedRadio);
    $i->seeElement("$submitButton:not(:disabled)");
    $i->seeNoJSErrors();
    $i->click($submitButton);
    $i->seeNoJSErrors();
    $i->waitForElement('[data-automation-id="create_standard"]');
  }

  /**
   * Test that admin is always redirected to WooCommerce list import page and
   * can't go to another page unless he submits the form
   * @param \AcceptanceTester $I
   */
  public function importListPageRedirectionTest(\AcceptanceTester $i) {
    $this->settings->withWooCommerceListImportPageDisplayed(false);
    $order = $this->orderFactory
      ->withDateCreated('2001-08-22T11:11:56') // any time in the past. Must be before the plugin activation
      ->create();
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->seeInCurrentUrl('wp-admin/admin.php?page=mailpoet-woocommerce-list-import');
    $i->amOnMailpoetPage('Emails');
    $i->seeInCurrentUrl('wp-admin/admin.php?page=mailpoet-woocommerce-list-import');
    $this->orderFactory->delete($order['id']);
  }
}
