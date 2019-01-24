<?php

namespace MailPoet\Test\Acceptance;

class NewslettersListingCest {

  function newslettersListing(\AcceptanceTester $I) {
    $I->wantTo('Open newsletters listings page');

    $I->login();
    $I->amOnMailpoetPage('Emails');

    // Standard newsletters is the default tab
    $I->waitForText('Standard newsletter', 5, '[data-automation-id="listing_item_1"]');

    $I->click('Welcome Emails', '[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForText('Welcome email', 5, '[data-automation-id="listing_item_2"]');

    $I->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForText('Post notification', 5, '[data-automation-id="listing_item_3"]');
    $I->seeNoJSErrors();
  }

  function statisticsColumn(\AcceptanceTester $I) {
    $I->wantTo('Check if statistics column is visible depending on tracking option');

    $I->login();

    // column is hidden when tracking is not enabled
    $I->amOnMailpoetPage('Settings');
    $I->click('[data-automation-id="settings-advanced-tab"]');
    $I->click('[data-automation-id="tracking-disabled-radio"]');
    $I->click('[data-automation-id="settings-submit-button"]');

    $I->amOnMailpoetPage('Emails');
    $I->waitForText('Subject');
    $I->dontSee('Opened, Clicked');

    // column is visible when tracking is enabled
    $I->amOnMailpoetPage('Settings');
    $I->click('[data-automation-id="settings-advanced-tab"]');
    $I->click('[data-automation-id="tracking-enabled-radio"]');
    $I->click('[data-automation-id="settings-submit-button"]');

    $I->amOnMailpoetPage('Emails');
    $I->waitForText('Subject');
    $I->see('Opened, Clicked');
    $I->seeNoJSErrors();
  }

}
