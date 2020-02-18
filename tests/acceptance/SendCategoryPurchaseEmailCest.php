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
    $settingsFactory->withCronTriggerMethod('WordPress');
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

    $i->amOnMailboxAppPage();
    $i->waitForText($emailSubject, 20);
    $i->click(Locator::contains('span.subject', $emailSubject));
    $i->waitForText($userEmail, 20);
  }
}
