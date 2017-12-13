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

  function statisticsColumn(\AcceptanceTester $I) {
    $I->wantTo('Check if statistics column is visible depending on tracking option');

    $I->loginAsAdmin();

    // column is hidden when tracking is not enabled
    $I->cli('db query "UPDATE mp_mailpoet_settings SET value = null WHERE name = \'tracking\'" --allow-root');
    $I->amOnMailpoetPage('Emails');
    $I->waitForText('Subject', 5);
    $I->dontSee('Opened, Clicked');

    // column is visible when tracking is enabled
    $I->cli('db query "UPDATE mp_mailpoet_settings SET value = \'a:1:{s:7:\"enabled\";s:1:\"1\";}\' WHERE name = \'tracking\'" --allow-root');
    $I->reloadPage();
    $I->waitForText('Subject', 5);
    $I->see('Opened, Clicked');
  }
}