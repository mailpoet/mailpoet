<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\WooCommerceProduct;

/**
 * @group woo
 * @group frontend
 */
class SendFirstPurchaseEmailCest {
  /** @var Settings */
  private $settingsFactory;

  public function _before(\AcceptanceTester $i) {
    $i->activateWooCommerce();
    $this->settingsFactory = new Settings();
    $this->settingsFactory->withWooCommerceListImportPageDisplayed(true);
    $this->settingsFactory->withWooCommerceCheckoutOptinEnabled();
    $this->settingsFactory->withConfirmationEmailDisabled();
    $this->settingsFactory->withCronTriggerMethod('Action Scheduler');
  }

  public function sendFirstPurchaseEmail(\AcceptanceTester $i) {
    $i->wantTo('Send a "First purchase email"');

    $productName = 'First Purchase Product';
    $productName = 'Second Purchase Product';
    $productFactory = new WooCommerceProduct($i);
    $product1 = $productFactory->withName($productName)->create();
    $product2 = $productFactory->withName($productName)->create();

    $emailSubject = 'First Purchase Test';
    $newsletterFactory = new Newsletter();
    $newsletterFactory
      ->withSubject($emailSubject)
      ->withAutomaticTypeWooCommerceFirstPurchase()
      ->withActiveStatus()
      ->create();

    $userEmail = 'user@email.test';
    $i->orderProduct($product1, $userEmail);
    $i->triggerMailPoetActionScheduler();
    $i->checkEmailWasReceived($emailSubject);

    $i->click(Locator::contains('span.subject', $emailSubject));
    $i->waitForText($userEmail, 20);

    $i->wantTo('Purchase second product and check if I didn\'t get "First purchase email"');

    $i->emptyMailbox();
    $i->amOnSiteHomepage();
    $i->orderProductWithoutRegistration($product2, $userEmail);
    $i->triggerMailPoetActionScheduler();
    $i->checkEmailWasNotReceived($emailSubject);
  }

  public function doNotSendFirstPurchaseEmailIfUserHasNotOptedIn(\AcceptanceTester $i) {
    $i->wantTo('Buy a product, do not opt-in and don\'t receive a "First purchase email"');

    $productName = 'First Purchase Product';
    $productFactory = new WooCommerceProduct($i);
    $product = $productFactory->withName($productName)->create();

    $emailSubject = 'First Purchase Test 2';
    $newsletterFactory = new Newsletter();
    $newsletterFactory
      ->withSubject($emailSubject)
      ->withAutomaticTypeWooCommerceFirstPurchase()
      ->withActiveStatus()
      ->create();

    $userEmail = 'user3@email.test';
    $i->orderProduct($product, $userEmail, true, false);

    $i->amOnMailboxAppPage();
    $i->dontSee($emailSubject);
    $i->dontSee($userEmail);
  }
}
