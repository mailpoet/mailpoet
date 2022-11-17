<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\Settings;

/**
 * This class contains tests for subscriptions
 * of guest customers done via checkout page
 * @group woo
 */
class WooMyAccountRegistrationCest {

  /** @var Settings */
  private $settingsFactory;

  public function _before(\AcceptanceTester $i) {
    $i->activateWooCommerce();
    $this->settingsFactory = new Settings();
    $this->settingsFactory->withWooCommerceListImportPageDisplayed(true);
    $this->settingsFactory->withCookieRevenueTrackingDisabled();
  }

  public function registerOptInDisabled(\AcceptanceTester $i) {
    $this->settingsFactory->withSubscribeOnRegisterDisabled();
    $this->settingsFactory->withConfirmationEmailEnabled();
    $customerEmail = 'woo_register_noptin@example.com';
    $i->registerCustomerOnMyAccountPage($customerEmail, false);
    $i->logOut();
    $i->login();
    $i->checkSubscriberStatusAndLists($customerEmail, SubscriberEntity::STATUS_UNCONFIRMED, ['WooCommerce Customers', 'WordPress Users']);
    $i->seeConfirmationEmailWasNotReceived();
  }

  public function registerOptInChecked(\AcceptanceTester $i) {
    $this->settingsFactory->withSubscribeOnRegisterEnabled();
    $this->settingsFactory->withConfirmationEmailEnabled();
    $customerEmail = 'woo_register_optin_checked@example.com';
    $i->registerCustomerOnMyAccountPage($customerEmail, true);
    $i->logOut();
    $i->login();
    $i->checkSubscriberStatusAndLists($customerEmail, SubscriberEntity::STATUS_UNCONFIRMED, ['WooCommerce Customers', 'WordPress Users']);
    $i->seeConfirmationEmailWasReceived();
  }

  public function registerOptInUnchecked(\AcceptanceTester $i) {
    $this->settingsFactory->withSubscribeOnRegisterEnabled();
    $this->settingsFactory->withConfirmationEmailEnabled();
    $customerEmail = 'woo_register_optin_unchecked@example.com';
    $i->registerCustomerOnMyAccountPage($customerEmail, false);
    $i->logOut();
    $i->login();
    $i->checkSubscriberStatusAndLists($customerEmail, SubscriberEntity::STATUS_UNSUBSCRIBED, ['WooCommerce Customers', 'WordPress Users']);
    $i->seeConfirmationEmailWasNotReceived();
  }

  public function registerOptInDisabledNoConfirmation(\AcceptanceTester $i) {
    $this->settingsFactory->withSubscribeOnRegisterDisabled();
    $this->settingsFactory->withConfirmationEmailDisabled();
    $customerEmail = 'woo_register_noptin_no_confirm@example.com';
    $i->registerCustomerOnMyAccountPage($customerEmail, false);
    $i->logOut();
    $i->login();
    $i->checkSubscriberStatusAndLists($customerEmail, SubscriberEntity::STATUS_SUBSCRIBED, ['WooCommerce Customers', 'WordPress Users']);
    $i->seeConfirmationEmailWasNotReceived();
  }

  public function registerOptInCheckedNoConfirmation(\AcceptanceTester $i) {
    $this->settingsFactory->withSubscribeOnRegisterEnabled();
    $this->settingsFactory->withConfirmationEmailDisabled();
    $customerEmail = 'woo_register_optin_checked_no_confirm@example.com';
    $i->registerCustomerOnMyAccountPage($customerEmail, true);
    $i->logOut();
    $i->login();
    $i->checkSubscriberStatusAndLists($customerEmail, SubscriberEntity::STATUS_SUBSCRIBED, ['WooCommerce Customers', 'WordPress Users']);
    $i->seeConfirmationEmailWasNotReceived();
  }

  public function registerOptInUncheckedNoConfirmation(\AcceptanceTester $i) {
    $this->settingsFactory->withSubscribeOnRegisterEnabled();
    $this->settingsFactory->withConfirmationEmailDisabled();
    $customerEmail = 'woo_register_optin_unchecked_no_confirm@example.com';
    $i->registerCustomerOnMyAccountPage($customerEmail, false);
    $i->logOut();
    $i->login();
    $i->checkSubscriberStatusAndLists($customerEmail, SubscriberEntity::STATUS_UNSUBSCRIBED, ['WooCommerce Customers', 'WordPress Users']);
    $i->seeConfirmationEmailWasNotReceived();
  }
}
