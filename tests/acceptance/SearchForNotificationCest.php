<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class SearchForNotificationCest {

  public function searchForStandardNotification(\AcceptanceTester $I) {
    $I->wantTo('Successfully search for an existing notification');
    $newsletter_title = 'Search Test Notification';
    $failure_condition_newsletter = 'Not Actually Real';
    // step 1 - Prepare newsletter data
    $newsletterFactory = new Newsletter();
    $newsletterFactory->withSubject($newsletter_title)
      ->withPostNotificationsType()
      ->create();
    // step 2 - Search
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForListingItemsToLoad();
    $I->searchFor($failure_condition_newsletter);
    $I->waitForElement('tr.no-items');
    $I->searchFor($newsletter_title);
    $I->waitForText($newsletter_title);
  }

}
