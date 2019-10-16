<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\WooCommerceProduct;

class SendFirstPurchaseEmailCest {
  /** @var Settings */
  private $settings_factory;

  function _before(\AcceptanceTester $I) {
    $I->activateWooCommerce();
    $this->settings_factory = new Settings();
    $this->settings_factory->withWooCommerceListImportPageDisplayed(true);
    $this->settings_factory->withWooCommerceCheckoutOptinEnabled();
    $this->settings_factory->withCronTriggerMethod('WordPress');
  }

  function sendFirstPurchaseEmail(\AcceptanceTester $I) {
    $I->wantTo('Send a "First purchase email"');

    $product_name = 'First Purchase Product';
    $product_factory = new WooCommerceProduct($I);
    $product = $product_factory->withName($product_name)->create();

    $email_subject = 'First Purchase Test';
    $newsletter_factory = new Newsletter();
    $newsletter_factory
      ->withSubject($email_subject)
      ->withAutomaticTypeWooCommerceFirstPurchase()
      ->withActiveStatus()
      ->create();

    $user_email = 'user@email.test';
    $I->orderProduct($product, $user_email);

    $I->amOnMailboxAppPage();
    $I->waitForText($email_subject, 20);
    $I->click(Locator::contains('span.subject', $email_subject));
    $I->waitForText($user_email, 20);
  }

  function doNotSendFirstPurchaseEmailIfUserHasNotOptedIn(\AcceptanceTester $I) {
    $I->wantTo('Buy a product, do not opt-in and don\'t receive a "First purchase email"');

    $product_name = 'First Purchase Product';
    $product_factory = new WooCommerceProduct($I);
    $product = $product_factory->withName($product_name)->create();

    $email_subject = 'First Purchase Test 2';
    $newsletter_factory = new Newsletter();
    $newsletter_factory
      ->withSubject($email_subject)
      ->withAutomaticTypeWooCommerceFirstPurchase()
      ->withActiveStatus()
      ->create();

    $user_email = 'user3@email.test';
    $I->orderProduct($product, $user_email, true, false);

    $I->amOnMailboxAppPage();
    $I->dontSee($email_subject);
    $I->dontSee($user_email);
  }
}
