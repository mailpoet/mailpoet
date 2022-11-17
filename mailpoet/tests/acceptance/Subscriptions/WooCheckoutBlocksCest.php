<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoet\Test\DataFactories\WooCommerceProduct;

/**
 * This class contains tests for subscriptions
 * of registered customers done via checkout page
 * @group woo
 */
class WooCheckoutBlocksCest {

  /** @var Settings */
  private $settingsFactory;

  /** @var array WooCommerce Product data*/
  private $product;

  /** @var int */
  private $checkoutPostId;

  /** @var string */
  private $checkoutPostContent = '';

  public function _before(\AcceptanceTester $i) {
    $i->activateWooCommerce();
    $i->activateWooCommerceBlocks();
    $this->product = (new WooCommerceProduct($i))->create();
    $this->settingsFactory = new Settings();
    $this->settingsFactory->withWooCommerceListImportPageDisplayed(true);
    $this->settingsFactory->withCookieRevenueTrackingDisabled();
    // We have to check minimal plugin versions before creating the WC checkout page
    if ($this->checkMinimalPluginVersions($i)) {
      // Due to test speed, we create a page via UI only for the first time, and then we copy the post content
      if (!$this->checkoutPostContent) {
        $checkoutPostId = $this->configureBlocksCheckoutPage($i);
        $postData = $i->cliToString(['post', 'get', $checkoutPostId, '--format=json']);
        $postData = json_decode($postData, true);
        $this->checkoutPostContent = is_array($postData) ? $postData['post_content'] : '';
        $this->checkoutPostId = $checkoutPostId;
      } else {
        $this->checkoutPostId = $this->createCheckoutPage($i, $this->checkoutPostContent);
      }
    }
  }

  public function checkoutOptInDisabled(\AcceptanceTester $i) {
    if (!$this->checkMinimalPluginVersions($i)) {
      $i->wantTo('Skip test due to minimal plugin version requirements');
      return;
    }
    $this->settingsFactory->withWooCommerceCheckoutOptinDisabled();
    $this->settingsFactory->withConfirmationEmailEnabled();
    $customerEmail = 'woo_customer_noptin@example.com';
    $i->wantTo('Check a message when opt-in is disabled');
    $i->login();
    $i->amOnAdminPage("post.php?post={$this->checkoutPostId}&action=edit");
    $i->canSee('MailPoet marketing opt-in would be shown here if enabled. You can enable from the settings page.');
    $i->logOut();
    $this->orderProduct($i, $customerEmail, true, false);
    $i->login();
    $i->checkSubscriberStatusAndLists($customerEmail, SubscriberEntity::STATUS_UNCONFIRMED, ['WordPress Users'], ['WooCommerce Customers']);
    $i->seeConfirmationEmailWasNotReceived();
  }

  public function checkoutOptInChecked(\AcceptanceTester $i) {
    if (!$this->checkMinimalPluginVersions($i)) {
      $i->wantTo('Skip test due to minimal plugin version requirements');
      return;
    }
    $this->settingsFactory->withWooCommerceCheckoutOptinEnabled();
    $this->settingsFactory->withConfirmationEmailEnabled();
    $customerEmail = 'woo_customer_check@example.com';
    $this->orderProduct($i, $customerEmail, true, true);
    $i->login();
    $i->checkSubscriberStatusAndLists($customerEmail, SubscriberEntity::STATUS_UNCONFIRMED, ['WooCommerce Customers', 'WordPress Users']);
    $i->seeConfirmationEmailWasReceived();
  }

  public function checkoutOptInUnchecked(\AcceptanceTester $i) {
    if (!$this->checkMinimalPluginVersions($i)) {
      $i->wantTo('Skip test due to minimal plugin version requirements');
      return;
    }
    $this->settingsFactory->withWooCommerceCheckoutOptinEnabled();
    $this->settingsFactory->withConfirmationEmailEnabled();
    $customerEmail = 'woo_customer_uncheck@example.com';
    $this->orderProduct($i, $customerEmail, true, false);
    $i->login();
    $i->checkSubscriberStatusAndLists($customerEmail, SubscriberEntity::STATUS_UNSUBSCRIBED, ['WordPress Users'], ['WooCommerce Customers']);
    $i->seeConfirmationEmailWasNotReceived();
  }

  public function checkoutOptInDisabledNoConfirmation(\AcceptanceTester $i) {
    if (!$this->checkMinimalPluginVersions($i)) {
      $i->wantTo('Skip test due to minimal plugin version requirements');
      return;
    }
    $this->settingsFactory->withWooCommerceCheckoutOptinDisabled();
    $this->settingsFactory->withConfirmationEmailDisabled();
    $customerEmail = 'woo_customer_noptin@example.com';
    $this->orderProduct($i, $customerEmail, true, false);
    $i->login();
    $i->checkSubscriberStatusAndLists($customerEmail, SubscriberEntity::STATUS_SUBSCRIBED, ['WordPress Users'], ['WooCommerce Customers']);
    $i->seeConfirmationEmailWasNotReceived();
  }

  public function checkoutOptInCheckedNoConfirmation(\AcceptanceTester $i) {
    if (!$this->checkMinimalPluginVersions($i)) {
      $i->wantTo('Skip test due to minimal plugin version requirements');
      return;
    }
    $this->settingsFactory->withWooCommerceCheckoutOptinEnabled();
    $this->settingsFactory->withConfirmationEmailDisabled();
    $customerEmail = 'woo_customer_check@example.com';
    $this->orderProduct($i, $customerEmail, true, true);
    $i->login();
    $i->checkSubscriberStatusAndLists($customerEmail, SubscriberEntity::STATUS_SUBSCRIBED, ['WooCommerce Customers', 'WordPress Users']);
    $i->seeConfirmationEmailWasNotReceived();
  }

  public function checkoutOptInUncheckedNoConfirmation(\AcceptanceTester $i) {
    if (!$this->checkMinimalPluginVersions($i)) {
      $i->wantTo('Skip test due to minimal plugin version requirements');
      return;
    }
    $this->settingsFactory->withWooCommerceCheckoutOptinEnabled();
    $this->settingsFactory->withConfirmationEmailDisabled();
    $customerEmail = 'woo_customer_uncheck@example.com';
    $this->orderProduct($i, $customerEmail, true, false);
    $i->login();
    $i->checkSubscriberStatusAndLists($customerEmail, SubscriberEntity::STATUS_UNSUBSCRIBED, ['WordPress Users'], ['WooCommerce Customers']);
    $i->seeConfirmationEmailWasNotReceived();
  }

  public function checkoutOptInDisabledExistingSubscriber(\AcceptanceTester $i) {
    if (!$this->checkMinimalPluginVersions($i)) {
      $i->wantTo('Skip test due to minimal plugin version requirements');
      return;
    }
    $this->settingsFactory->withWooCommerceCheckoutOptinDisabled();
    $list = (new Segment())->create();
    $customerEmail = 'woo_customer_disabled_exist@example.com';
    (new Subscriber())
      ->withEmail($customerEmail)
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$list])
      ->create();

    $this->orderProduct($i, $customerEmail, true, false);
    $i->login();
    $i->checkSubscriberStatusAndLists($customerEmail, SubscriberEntity::STATUS_SUBSCRIBED, [$list->getName()], ['WooCommerce Customers']);
    $i->seeConfirmationEmailWasNotReceived();
  }

  public function checkoutOptInUncheckedExistingSubscriber(\AcceptanceTester $i) {
    if (!$this->checkMinimalPluginVersions($i)) {
      $i->wantTo('Skip test due to minimal plugin version requirements');
      return;
    }
    $this->settingsFactory->withWooCommerceCheckoutOptinEnabled();
    $list = (new Segment())->create();
    $customerEmail = 'woo_customer_uncheck_exist@example.com';
    (new Subscriber())
      ->withEmail($customerEmail)
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$list])
      ->create();

    $this->orderProduct($i, $customerEmail, true, false);
    $i->login();
    $i->checkSubscriberStatusAndLists($customerEmail, SubscriberEntity::STATUS_SUBSCRIBED, [$list->getName()], ['WooCommerce Customers']);
    $i->seeConfirmationEmailWasNotReceived();
  }

  private function orderProduct(\AcceptanceTester $i, string $userEmail, bool $doRegister = true, bool $doSubscribe = true): void {
    $i->addProductToCart($this->product);
    $i->amOnPage("/?p={$this->checkoutPostId}");
    $this->fillBlocksCustomerInfo($i, $userEmail);
    if ($doSubscribe) {
      $settings = (ContainerWrapper::getInstance())->get(SettingsController::class);
      $i->click(Locator::contains('label', $settings->get('woocommerce.optin_on_checkout.message')));
    }
    if ($doRegister) {
      $i->click(Locator::contains('label', 'Create an account?'));
    }
    $this->placeOrder($i);
    $i->logOut();
  }

  private function configureBlocksCheckoutPage(\AcceptanceTester $i): int {
    $postId = $this->createCheckoutPage($i);
    $i->wantTo('Create the Checkout Block page');
    $i->login();
    $i->amOnAdminPage("post.php?post={$postId}&action=edit");

    // Close welcome in block editor dialog
    $this->closeDialog($i);
    $i->click('button[aria-label="Toggle block inserter"]');
    $i->fillField('[placeholder="Search"]', 'Checkout');
    $i->click('Checkout');
    // Close dialog with Compatibility notice
    $this->closeDialog($i);

    // Enable registration during the checkout
    $i->click('[aria-label="Block: Contact Information"]');
    $i->click('[aria-label="Settings"]');
    $i->click(Locator::contains('label', 'Allow shoppers to sign up for a user account during checkout'));

    $i->click('Update');
    $i->waitForText('Page updated.');
    $i->logOut();
    return $postId;
  }

  /**
   * Close dialog when is visible.
   */
  private function closeDialog(\AcceptanceTester $i): void {
    $closeButton = $i->executeJS('return document.querySelectorAll("button[aria-label=\'Close dialog\']");');
    if ($closeButton) {
      $i->click('button[aria-label="Close dialog"]');
    }
  }

  private function createCheckoutPage(\AcceptanceTester $i, string $postContent = ''): int {
    return (int)$i->cliToString([
      'post',
      'create',
      '--format=json',
      '--porcelain',
      '--post_author=1',
      '--post_status=publish',
      '--post_type=page',
      '--post_name=wcb-checkout',
      '--post_title="WC Blocks Checkout"',
      '--post_content=\'' . $postContent . '\'',
    ]);
  }

  private function fillBlocksCustomerInfo(\AcceptanceTester $i, string $userEmail): void {
    $i->fillField('#billing-first_name', 'John');
    $i->fillField('#billing-last_name', 'Doe');
    $i->fillField('#billing-address_1', 'Address 1');
    $i->fillField('#billing-city', 'Paris');
    $i->fillField('#email', $userEmail);
    $i->fillField('#billing-postcode', '75000');
  }

  private function placeOrder(\AcceptanceTester $i): void {
    $i->waitForElementClickable(Locator::contains('button', 'Place Order'));
    $i->click(Locator::contains('button', 'Place Order'));
    $i->waitForText('Your order has been received');
  }

  private function checkMinimalPluginVersions(\AcceptanceTester $i): bool {
    $wooCommerceVersion = $i->getWooCommerceVersion();
    if (version_compare($wooCommerceVersion, '6.8.0', '<')) {
      return false;
    }
    $wooCommerceBlocksVersion = $i->getWooCommerceBlocksVersion();
    if (version_compare($wooCommerceBlocksVersion, '8.0.0', '<')) {
      return false;
    }
    $wordPressVersion = $i->getWordPressVersion();
    if (version_compare($wordPressVersion, '5.8', '<')) {
      return false;
    }
    return true;
  }
}
