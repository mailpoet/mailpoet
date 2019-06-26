<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Features\FeaturesController;
use MailPoet\Test\DataFactories\Features;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\NewsletterLink;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\StatisticsClicks;
use MailPoet\Test\DataFactories\StatisticsWooCommercePurchases;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoet\Test\DataFactories\WooCommerceOrder;
use MailPoet\Test\DataFactories\WooCommerceProduct;

class StatsPageCest {

  function statsPage(\AcceptanceTester $I) {
    $I->wantTo('Open stats page of a sent newsletter');

    $newsletter_title = 'Stats Page Test';
    (new Newsletter())->withSubject($newsletter_title)
      ->withSentStatus()
      ->withSendingQueue()
      ->create();

    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->waitForText($newsletter_title);
    $I->clickItemRowActionByItemName($newsletter_title, 'Statistics');
    $I->waitForText('Stats: ' . $newsletter_title);
    $I->see('Buy the Premium to see your stats');

    $href = $I->grabAttributeFrom('//a[text()="Sign Up for Free"]', 'href');
    expect($href)->equals('https://www.mailpoet.com/free-plan/');
    $href = $I->grabAttributeFrom('//a[text()="Learn more about Premium"]', 'href');
    expect($href)->endsWith('page=mailpoet-premium');
  }

}
