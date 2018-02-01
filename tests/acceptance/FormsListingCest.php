<?php

namespace MailPoet\Test\Acceptance;

class FormsListingCest {
  function formsListing(\AcceptanceTester $I) {
    $I->wantTo('Open forms listings page');

    $I->login();
    $I->amOnMailpoetPage('Forms');

    $I->waitForText('Test Form', 5, '[data-automation-id="listing_item_1"]');
  }
}