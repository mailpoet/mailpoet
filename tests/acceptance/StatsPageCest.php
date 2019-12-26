<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class StatsPageCest {

  public function statsPage(\AcceptanceTester $I) {
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
    expect($href)->contains('https://www.mailpoet.com/free-plan?utm_medium=stats&utm_campaign=signup&utm_source=plugin');
    $href = $I->grabAttributeFrom('//a[text()="Learn more about Premium"]', 'href');
    expect($href)->endsWith('page=mailpoet-premium');
  }

}
