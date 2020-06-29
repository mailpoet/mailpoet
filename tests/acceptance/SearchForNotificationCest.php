<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class SearchForNotificationCest {
  public function searchForStandardNotification(\AcceptanceTester $i) {
    $i->wantTo('Successfully search for an existing notification');
    $newsletterTitle = 'Search Test Notification';
    $failureConditionNewsletter = 'Not Actually Real';
    // step 1 - Prepare newsletter data
    $newsletterFactory = new Newsletter();
    $newsletterFactory->withSubject($newsletterTitle)
      ->withPostNotificationsType()
      ->create();
    // step 2 - Search
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');
    $i->waitForListingItemsToLoad();
    $i->searchFor($failureConditionNewsletter);
    $i->waitForElement('tr.mailpoet-listing-no-items');
    $i->searchFor($newsletterTitle);
    $i->waitForText($newsletterTitle);
  }
}
