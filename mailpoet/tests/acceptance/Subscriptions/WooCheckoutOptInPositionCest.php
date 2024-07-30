<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Config\Hooks;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\WooCommerceProduct;

/**
 * Tests for opt-in checkbox position on legacy WooCommerce shortcode checkout page
 * @group woo
 * @group frontend
 */
class WooCheckoutOptInPositionCest {

  const OPTIN_SELECTOR = '[data-automation-id="woo-commerce-subscription-opt-in"]';

  /** @var Settings */
  private $settingsFactory;

  /** @var array WooCommerce Product data */
  private $product;

  public function _before(\AcceptanceTester $i) {
    $i->activateWooCommerce();
    $this->product = (new WooCommerceProduct($i))->create();
    $this->settingsFactory = new Settings();
    $this->settingsFactory->withWooCommerceListImportPageDisplayed(true);
    $this->settingsFactory->withCookieRevenueTrackingDisabled();
    $this->settingsFactory->withWooCommerceCheckoutOptinEnabled();
  }

  public function optInPositionAfterBillingInfo(\AcceptanceTester $i) {
    $this->settingsFactory->withWooCommerceCheckoutOptinPosition(Hooks::OPTIN_POSITION_AFTER_BILLING_INFO);
    $this->goToCheckout($i);
    $i->waitForElement('.woocommerce-billing-fields__field-wrapper ~ ' . self::OPTIN_SELECTOR);
  }

  public function optInPositionAfterOrderNotes(\AcceptanceTester $i) {
    $this->settingsFactory->withWooCommerceCheckoutOptinPosition(Hooks::OPTIN_POSITION_AFTER_ORDER_NOTES);
    $this->goToCheckout($i);
    $i->waitForElement('.woocommerce-additional-fields__field-wrapper ~ ' . self::OPTIN_SELECTOR);
  }

  public function optInPositionAfterTermsAndConditions(\AcceptanceTester $i) {
    $this->settingsFactory->withWooCommerceCheckoutOptinPosition(Hooks::OPTIN_POSITION_AFTER_TERMS_AND_CONDITIONS);
    $this->goToCheckout($i);
    $i->waitForElement('.woocommerce-terms-and-conditions-wrapper ~ ' . self::OPTIN_SELECTOR);
  }

  public function optInPositionBeforePaymentMethods(\AcceptanceTester $i) {
    $this->settingsFactory->withWooCommerceCheckoutOptinPosition(Hooks::OPTIN_POSITION_BEFORE_PAYMENT_METHODS);
    $this->goToCheckout($i);
    $i->waitForElement(self::OPTIN_SELECTOR . ' ~ .woocommerce-checkout-payment');
  }

  public function optInPositionBeforeTermsAndConditions(\AcceptanceTester $i) {
    $this->settingsFactory->withWooCommerceCheckoutOptinPosition(Hooks::OPTIN_POSITION_BEFORE_TERMS_AND_CONDITIONS);
    $this->goToCheckout($i);
    $i->waitForElement(self::OPTIN_SELECTOR . ' ~ .woocommerce-terms-and-conditions-wrapper');
  }

  private function goToCheckout(\AcceptanceTester $i) {
    $i->addProductToCart($this->product);
    $i->goToShortcodeCheckout();
    $i->waitForText(Settings::DEFAULT_OPTIN_MESSAGE);
  }
}
