<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\WooCommerceProduct;

/**
 * This class contains tests for subscriptions
 * of customers done via checkout page with
 * AutomateWoo plugin active which has its own
 * checkout opt-in functionality
 * @group woo
 */
class WooCheckoutAutomateWooSubscriptionsCest {

  const MAILPOET_OPTIN_TEXT = 'Yes, I would like to be added to your mailing list';
  const AUTOMATE_WOO_OPTIN_TEXT = 'I want to receive updates about products and promotions';

  /** @var Settings */
  private $settingsFactory;

  /** @var array WooCommerce Product data*/
  private $product;

  public function _before(\AcceptanceTester $i) {
    $i->activateWooCommerce();
    $i->activateAutomateWoo();
    $i->login();
    $i->amOnPage('/wp-admin/');
    $this->product = (new WooCommerceProduct($i))->create();
    $this->settingsFactory = new Settings();
    $this->settingsFactory->withWooCommerceListImportPageDisplayed(true);
    $this->settingsFactory->withCookieRevenueTrackingDisabled();
    // Let AutomateWoo settings to be applied
    $i->amOnPage('/wp-admin/admin.php?page=automatewoo-settings');
    $i->seeCheckboxIsChecked('#automatewoo_enable_checkout_optin');
    $i->logout();
  }

  public function checkoutOptInDisabled(\AcceptanceTester $i) {
    $this->settingsFactory->withWooCommerceCheckoutOptinDisabled();
    $i->addProductToCart($this->product);
    $i->goToCheckout();
    $i->waitForText(self::AUTOMATE_WOO_OPTIN_TEXT, 10);
    $i->dontSee(self::MAILPOET_OPTIN_TEXT);
  }

  public function checkoutOptInEnabled(\AcceptanceTester $i, $scenario) {
    $this->settingsFactory->withWooCommerceCheckoutOptinEnabled();
    $i->addProductToCart($this->product);
    $i->goToCheckout();
    $i->waitForText(self::MAILPOET_OPTIN_TEXT, 10);
    $i->dontSee(self::AUTOMATE_WOO_OPTIN_TEXT);
  }

  public function checkoutOptInUnchecked(\AcceptanceTester $i) {
    $this->settingsFactory->withWooCommerceCheckoutOptinEnabled();
    $this->settingsFactory->withConfirmationEmailEnabled();
    $customerEmail = 'woo_guest_uncheck@example.com';
    $i->orderProductWithoutRegistration($this->product, $customerEmail, false);
    $i->login();
    $i->checkSubscriberStatusAndLists($customerEmail, SubscriberEntity::STATUS_UNCONFIRMED, null, ['WooCommerce Customers']);
    $i->amOnPage('/wp-admin/admin.php?page=automatewoo-opt-ins');
    $i->dontSee($customerEmail, '.automatewoo-content');
    $i->seeConfirmationEmailWasNotReceived();
  }

  public function checkoutOptInChecked(\AcceptanceTester $i, $scenario) {
    $this->settingsFactory->withWooCommerceCheckoutOptinEnabled();
    $this->settingsFactory->withConfirmationEmailEnabled();
    $customerEmail = 'woo_guest_check@example.com';
    $i->orderProductWithRegistration($this->product, $customerEmail, true);
    $i->login();
    $i->checkSubscriberStatusAndLists($customerEmail, SubscriberEntity::STATUS_UNCONFIRMED, ['WooCommerce Customers']);
    $i->amOnPage('/wp-admin/admin.php?page=automatewoo-opt-ins');
    $i->see($customerEmail, '.automatewoo-content');
    $i->seeConfirmationEmailWasReceived();
  }
}
