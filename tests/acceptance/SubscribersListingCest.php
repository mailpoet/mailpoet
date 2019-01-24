<?php

namespace MailPoet\Test\Acceptance;

class SubscribersListingCest {

  function subscribersListing(\AcceptanceTester $I) {
    $I->wantTo('Open subscribers listings page');

    $I->login();
    $I->amOnMailpoetPage('Subscribers');
    $I->searchFor('wp@example.com', 2);
    $I->waitForText('wp@example.com');
  }

}
