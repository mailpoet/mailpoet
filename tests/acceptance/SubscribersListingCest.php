<?php

namespace MailPoet\Test\Acceptance;

class SubscribersListingCest {
  function subscribersListing(\AcceptanceTester $I) {
    $I->wantTo('Open subscribers listings page');

    $I->loginAsAdmin();
    $I->amOnMailpoetPage('Subscribers');

    $I->waitForText('wp@example.com', 5, '[data-automation-id="listing_item_1"]');
    $I->see('subscriber@example.com', '[data-automation-id="listing_item_2"]');
  }
}