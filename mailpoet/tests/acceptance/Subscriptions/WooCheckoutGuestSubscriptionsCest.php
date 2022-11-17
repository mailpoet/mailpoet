<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoet\Test\DataFactories\WooCommerceProduct;

/**
 * This class contains tests for subscriptions
 * of guest customers done via checkout page
 * @group woo
 */
class WooCheckoutGuestSubscriptionsCest {

  /** @var Settings */
  private $settingsFactory;

  /** @var array WooCommerce Product data*/
  private $product;

  public function _before(\AcceptanceTester $i) {
    $i->activateWooCommerce();
    $this->product = (new WooCommerceProduct($i))->create();
    $this->settingsFactory = new Settings();
    $this->settingsFactory->withWooCommerceListImportPageDisplayed(true);
    $this->settingsFactory->withCookieRevenueTrackingDisabled();
  }

  public function checkoutOptInDisabled(\AcceptanceTester $i) {
    $this->settingsFactory->withWooCommerceCheckoutOptinDisabled();
    $this->settingsFactory->withConfirmationEmailEnabled();
    $customerEmail = 'woo_guest_noptin@example.com';
    $i->orderProductWithoutRegistration($this->product, $customerEmail, false);
    $i->login();
    $i->checkSubscriberStatusAndLists($customerEmail, SubscriberEntity::STATUS_UNCONFIRMED, null, ['WooCommerce Customers']);
    $i->seeConfirmationEmailWasNotReceived();
  }

  public function checkoutOptInChecked(\AcceptanceTester $i) {
    $this->settingsFactory->withWooCommerceCheckoutOptinEnabled();
    $this->settingsFactory->withConfirmationEmailEnabled();
    $customerEmail = 'woo_guest_check@example.com';
    $i->orderProductWithoutRegistration($this->product, $customerEmail, true);
    $i->login();
    $i->checkSubscriberStatusAndLists($customerEmail, SubscriberEntity::STATUS_UNCONFIRMED, ['WooCommerce Customers']);
    $i->seeConfirmationEmailWasReceived();
  }

  public function checkoutOptInUnchecked(\AcceptanceTester $i) {
    $this->settingsFactory->withWooCommerceCheckoutOptinEnabled();
    $this->settingsFactory->withConfirmationEmailEnabled();
    $customerEmail = 'woo_guest_uncheck@example.com';
    $i->orderProductWithoutRegistration($this->product, $customerEmail, false);
    $i->login();
    $i->checkSubscriberStatusAndLists($customerEmail, SubscriberEntity::STATUS_UNSUBSCRIBED, null, ['WooCommerce Customers']);
    $i->seeConfirmationEmailWasNotReceived();
  }

  public function checkoutOptInDisabledNoConfirmation(\AcceptanceTester $i) {
    $this->settingsFactory->withWooCommerceCheckoutOptinDisabled();
    $this->settingsFactory->withConfirmationEmailDisabled();
    $customerEmail = 'woo_guest_noptin@example.com';
    $i->orderProductWithoutRegistration($this->product, $customerEmail, false);
    $i->login();
    $i->checkSubscriberStatusAndLists($customerEmail, SubscriberEntity::STATUS_SUBSCRIBED, null, ['WooCommerce Customers']);
    $i->seeConfirmationEmailWasNotReceived();
  }

  public function checkoutOptInCheckedNoConfirmation(\AcceptanceTester $i) {
    $this->settingsFactory->withWooCommerceCheckoutOptinEnabled();
    $this->settingsFactory->withConfirmationEmailDisabled();
    $customerEmail = 'woo_guest_check@example.com';
    $i->orderProductWithoutRegistration($this->product, $customerEmail, true);
    $i->login();
    $i->checkSubscriberStatusAndLists($customerEmail, SubscriberEntity::STATUS_SUBSCRIBED, ['WooCommerce Customers']);
    $i->seeConfirmationEmailWasNotReceived();
  }

  public function checkoutOptInUncheckedNoConfirmation(\AcceptanceTester $i) {
    $this->settingsFactory->withWooCommerceCheckoutOptinEnabled();
    $this->settingsFactory->withConfirmationEmailDisabled();
    $customerEmail = 'woo_guest_uncheck@example.com';
    $i->orderProductWithoutRegistration($this->product, $customerEmail, false);
    $i->login();
    $i->checkSubscriberStatusAndLists($customerEmail, SubscriberEntity::STATUS_UNSUBSCRIBED, null, ['WooCommerce Customers']);
    $i->seeConfirmationEmailWasNotReceived();
  }

  public function checkoutOptInDisabledExistingSubscriber(\AcceptanceTester $i) {
    $this->settingsFactory->withWooCommerceCheckoutOptinDisabled();
    $list = (new Segment())->create();
    $customerEmail = 'woo_guest_disabled_exist@example.com';
    (new Subscriber())
      ->withEmail($customerEmail)
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$list])
      ->create();

    $i->orderProductWithoutRegistration($this->product, $customerEmail, false);
    $i->login();
    $i->checkSubscriberStatusAndLists($customerEmail, SubscriberEntity::STATUS_SUBSCRIBED, [$list->getName()], ['WooCommerce Customers']);
    $i->seeConfirmationEmailWasNotReceived();
  }

  public function checkoutOptInUncheckedExistingSubscriber(\AcceptanceTester $i) {
    $this->settingsFactory->withWooCommerceCheckoutOptinEnabled();
    $list = (new Segment())->create();
    $customerEmail = 'woo_guest_uncheck_exist@example.com';
    (new Subscriber())
      ->withEmail($customerEmail)
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$list])
      ->create();

    $i->orderProductWithoutRegistration($this->product, $customerEmail, false);
    $i->login();
    $i->checkSubscriberStatusAndLists($customerEmail, SubscriberEntity::STATUS_SUBSCRIBED, [$list->getName()], ['WooCommerce Customers']);
    $i->seeConfirmationEmailWasNotReceived();
  }
}
