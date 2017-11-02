<?php

namespace MailPoet\Test\Acceptance;

class NewsletterListingCest {
  function newsletterListing(\AcceptanceTester $I) {
    $I->wantTo('Open newsletters listings page');

    $I->loginAsAdmin();
    $I->seeInCurrentUrl('/wp-admin/');

    // Go to Status
    $I->amOnMailpoetPage('Emails');
    $I->waitForElement('#newsletters_container', 3);
  }
}