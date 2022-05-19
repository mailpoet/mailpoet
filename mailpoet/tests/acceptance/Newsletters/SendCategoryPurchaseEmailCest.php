<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\WooCommerceProduct;
use MailPoet\Util\Security;

class SendCategoryPurchaseEmailCest {
  public function _before(\AcceptanceTester $i) {
    $i->activateWooCommerce();
    $settingsFactory = new Settings();
    $settingsFactory->withWooCommerceListImportPageDisplayed(true);
    $settingsFactory->withWooCommerceCheckoutOptinEnabled();
    $settingsFactory->withConfirmationEmailDisabled();
    $settingsFactory->withCronTriggerMethod('Action Scheduler');
  }

  public function sendCategoryPurchaseEmail(\AcceptanceTester $i) {
    $i->wantTo('Buy a product in category and receive a "Purchased In This Category" email');

    $productName = 'Category Purchase Test Product';

    $productFactory = new WooCommerceProduct($i);

    $categoryId = $productFactory->createCategory('Category 1');
    $product = $productFactory
      ->withName($productName)
      ->withCategoryIds([$categoryId])
      ->create();

    $emailSubject = 'Product In Category Purchase Test';
    $newsletterFactory = new Newsletter();
    $newsletterFactory
      ->withSubject($emailSubject)
      ->withAutomaticTypeWooCommerceProductInCategoryPurchased([$product])
      ->withActiveStatus()
      ->create();

    $userEmail = Security::generateRandomString() . '-user@email.example';
    $i->orderProduct($product, $userEmail);

    $i->checkEmailWasReceived($emailSubject);
    $i->click(Locator::contains('span.subject', $emailSubject));
    $i->waitForText($userEmail, 20);
  }

  public function doNotSendCategoryPurchaseEmail(\AcceptanceTester $i) {
    $i->wantTo('Buy a product in category and do not receive a "Purchased In This Category" email');

    $productName = 'Category Purchase Test Product';
    $productFactory = new WooCommerceProduct($i);
    $categoryId = $productFactory->createCategory('Category 1');
    $product = $productFactory
      ->withName($productName)
      ->withCategoryIds([$categoryId])
      ->create();

    $emailSubject = 'Product In Category Purchase Test';
    $newsletterFactory = new Newsletter();
    $newsletterFactory
      ->withSubject($emailSubject)
      ->withAutomaticTypeWooCommerceProductInCategoryPurchased([$product])
      ->withActiveStatus()
      ->create();

    $userEmail = Security::generateRandomString() . '-user@email.example';
    $i->orderProduct($product, $userEmail, true, false);

    $this->verifyNoScheduledSending($i, $emailSubject);

    $i->amOnMailboxAppPage();
    $i->dontSee($emailSubject);
    $i->dontSee($userEmail);
  }

  private function verifyNoScheduledSending(\AcceptanceTester $i, $emailSubject) {
    $i->login();
    $i->amOnMailpoetPage('Help');
    $i->click('System Status');
    $i->dontSee($emailSubject);
  }
}
