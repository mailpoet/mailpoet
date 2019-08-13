<?php

namespace MailPoet\Test\Acceptance;

class ListsListingCest {

  function listsListing(\AcceptanceTester $I) {
    $I->wantTo('Open lists listings page');
    $I->deactivateWooCommerce();

    $I->login();
    $I->amOnMailpoetPage('Lists');

    $I->waitForText('WordPress Users', 5, '[data-automation-id="listing_item_1"]');
    $I->dontSee('WooCommerce Customers', '[data-automation-id="listing_item_2"]');
    $I->see('My First List', '[data-automation-id="listing_item_3"]');
    $I->seeNoJSErrors();
  }

}
