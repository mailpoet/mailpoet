<?php

namespace MailPoet\Test\Acceptance;

class ListsListingCest {

  public function listsListing(\AcceptanceTester $i) {
    $i->wantTo('Open lists listings page');

    $i->login();
    $i->amOnMailpoetPage('Lists');

    $i->waitForText('WordPress Users', 5, '[data-automation-id="listing_item_1"]');
    $i->see('My First List', '[data-automation-id="listing_item_3"]');
    $i->seeNoJSErrors();
  }

}
