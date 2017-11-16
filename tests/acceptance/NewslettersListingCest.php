<?php

namespace MailPoet\Test\Acceptance;

class NewslettersListingCest {
  function newslettersListing(\AcceptanceTester $I) {
    $I->wantTo('Open newsletters listings page');

    $I->loginAsAdmin();
    $I->amOnMailpoetPage('Emails');

    // Standard newsletters is the default tab
    $I->waitForText('Standard newsletter', 5, '[data-automation-id="listing_item_1"]');

    $I->click('Welcome Emails', '[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForText('Welcome email', 5, '[data-automation-id="listing_item_2"]');

    $I->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForText('Post notification', 5, '[data-automation-id="listing_item_3"]');
  }
}