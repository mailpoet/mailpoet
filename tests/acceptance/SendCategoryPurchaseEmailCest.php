<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\WooCommerceProduct;
use MailPoet\Util\Security;

class SendCategoryPurchaseEmailCest {

  function _before(\AcceptanceTester $I) {
    $I->activateWooCommerce();
    $settings_factory = new Settings();
    $settings_factory->withWooCommerceListImportPageDisplayed(true);
    $settings_factory->withWooCommerceCheckoutOptinEnabled();
    $settings_factory->withCronTriggerMethod('WordPress');
  }

  function sendCategoryPurchaseEmail(\AcceptanceTester $I) {
    $I->wantTo('Buy a product in category and receive a "Purchased In This Category" email');

    $product_name = 'Category Purchase Test Product';

    $product_factory = new WooCommerceProduct($I);

    $category_id = $product_factory->createCategory('Category 1');
    $product = $product_factory
      ->withName($product_name)
      ->withCategoryIds([$category_id])
      ->create();

    $email_subject = 'Product In Category Purchase Test';
    $newsletter_factory = new Newsletter();
    $newsletter_factory
      ->withSubject($email_subject)
      ->withAutomaticTypeWooCommerceProductInCategoryPurchased([$product])
      ->withActiveStatus()
      ->create();

    $user_email = Security::generateRandomString() . '-user@email.example';
    $I->orderProduct($product, $user_email);

    $I->amOnMailboxAppPage();
    $I->waitForText($email_subject, 20);
    $I->click(Locator::contains('span.subject', $email_subject));
    $I->waitForText($user_email, 20);
  }
}
