<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\WooCommerceCustomer;
use MailPoet\Test\DataFactories\WooCommerceOrder;
use PHPUnit\Framework\Exception;

class WooCommerceSetupPageCest {

  /** @var WooCommerceCustomer */
  private $customerFactory;

  /** @var Settings */
  private $settings;

  /** @var WooCommerceOrder*/
  private $orderFactory;

  public function _before(\AcceptanceTester $i) {
    $this->settings = new Settings();
    $i->activateWooCommerce();
    $this->customerFactory = new WooCommerceCustomer($i);
    $this->orderFactory = new WooCommerceOrder($i);
  }

  public function setupPageImportTest(\AcceptanceTester $i) {
    $this->settings
      ->withWooCommerceListImportPageDisplayed(false)
      ->withCronTriggerMethod('WordPress');
    $order = $this->orderFactory->create();
    $guestUserData = $order['billing'];
    $registeredCustomer = $this->customerFactory->withEmail('customer1@email.com')->create();
    $i->login();
    $i->amOnPage('wp-admin/admin.php?page=mailpoet-woocommerce-setup');
    $importTypeToggle = '[data-automation-id="woocommerce_import_type"]';
    $trackingToggle = '[data-automation-id="woocommerce_tracking"]';
    $submitButton = '[data-automation-id="submit_woocommerce_setup"]';
    $i->clickToggleYes($importTypeToggle); // import as subscribed
    $i->clickToggleYes($trackingToggle);
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
    for ($index = 0; $index < 15; $index++) {
      try {
        $i->wait(2);
        $i->reloadPage();
        $i->see($guestUserData['email']);
        return;
      } catch (Exception $e) {
        continue;
      }
    }
    $i->see($guestUserData['email']);
  }

  public function setupPageFormBehaviourTest(\AcceptanceTester $i) {
    $i->wantTo('Make sure the form shows errors when it is submitted without making choices');
    $i->login();
    $i->amOnPage('wp-admin/admin.php?page=mailpoet-woocommerce-setup');
    $i->see('Get ready to use MailPoet for WooCommerce');
    $importTypeToggle = '[data-automation-id="woocommerce_import_type"]';
    $trackingToggle = '[data-automation-id="woocommerce_tracking"]';
    $submitButton = '[data-automation-id="submit_woocommerce_setup"]';
    $errorClass = '.mailpoet-form-yesno-error';
    $i->dontSeeElement($errorClass);
    $i->click($submitButton);
    $i->seeElement($errorClass);
    $i->clickToggleYes($importTypeToggle);
    $i->click($submitButton);
    $i->seeElement($errorClass);
    $i->clickToggleYes($trackingToggle);
    $i->click($submitButton);
    $i->dontSeeElement($errorClass);
    $i->seeNoJSErrors();
    $i->waitForElement('[data-automation-id="create_standard"]');
  }

  /**
   * Test that admin is always redirected to WooCommerce setup page and
   * can't go to another page unless he submits the form
   * @param \AcceptanceTester $i
   */
  public function setupPageRedirectionTest(\AcceptanceTester $i) {
    $this->settings->withWooCommerceListImportPageDisplayed(false);
    $order = $this->orderFactory
      ->withDateCreated('2001-08-22T11:11:56') // any time in the past. Must be before the plugin activation
      ->create();
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->seeInCurrentUrl('wp-admin/admin.php?page=mailpoet-woocommerce-setup');
    $i->amOnMailpoetPage('Emails');
    $i->seeInCurrentUrl('wp-admin/admin.php?page=mailpoet-woocommerce-setup');
    $this->orderFactory->delete($order['id']);
  }
}
