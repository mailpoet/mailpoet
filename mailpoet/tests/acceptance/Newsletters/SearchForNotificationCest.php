<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;

class SearchForNotificationCest {
  public function searchForStandardNotification(\AcceptanceTester $i) {
    $i->wantTo('Successfully search for an existing and active notification');

    $newsletterTitle = 'Active Post Notification';
    $failureConditionNewsletter = 'Not Actually Real';
    $segmentName = 'Fancy List';

    // step 1 - Prepare newsletter data
    $segmentFactory = new Segment();
    $segment = $segmentFactory->withName($segmentName)->create();
    $newsletterFactory = new Newsletter();
    $newsletterFactory->withSubject($newsletterTitle)
      ->withPostNotificationsType()
      ->withSegments([$segment])
      ->withActiveStatus()
      ->create();

    // step 2 - Search
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');
    $i->waitForListingItemsToLoad();
    $i->searchFor($failureConditionNewsletter);
    $i->waitForText('No emails found.', 15, '[data-automation-id="newsletters_listing_tabs"]');

    // step 3 - Filter by Active and search for the active post notification
    $i->click('[data-automation-id="filters_active"]');
    $i->waitForText('No emails found.', 15, '[data-automation-id="newsletters_listing_tabs"]');
    $i->searchFor($newsletterTitle);
    $i->waitForText($newsletterTitle, 15, '[data-automation-id="newsletters_listing_tabs"]');

    // step 4 - Filter by assigned list and make sure the notification is present
    $i->selectOption('[data-automation-id="listing_filter_segment"]', $segmentName);
    $i->waitForElement('[data-automation-id="listing_filter_segment"]');
    $i->waitForText($newsletterTitle, 15, '[data-automation-id="newsletters_listing_tabs"]');

    // step 5 - Filter by Inactive and search for the active post notification
    $i->click('[data-automation-id="filters_not_active"]');
    $i->searchFor($newsletterTitle);
    $i->waitForText('No emails found.', 15, '[data-automation-id="newsletters_listing_tabs"]');
  }
}
