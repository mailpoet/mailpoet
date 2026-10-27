<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;

class SearchForNotificationCest {
  private function filterChip(string $name): array {
    return ['xpath' => '//*[contains(@class, "dataviews-filters__summary-chip-container")][contains(., "' . $name . '")]'];
  }

  public function searchForActiveStandardNotification(\AcceptanceTester $i) {
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
    $i->applyDataViewsFilter('Status', ['Active']);
    $i->waitForHashFilterValue('status', 'active');
    $i->waitForText('No emails found.', 15, '[data-automation-id="newsletters_listing_tabs"]');
    $i->searchFor($newsletterTitle);
    $i->waitForText($newsletterTitle, 15, '[data-automation-id="newsletters_listing_tabs"]');

    // step 4 - Filter by assigned list and make sure the notification is present
    $i->applyDataViewsFilter('Lists', [$segmentName]);
    $i->waitForHashFilterValue('segment', (string)$segment->getId());
    $i->waitForText($newsletterTitle, 15, '[data-automation-id="newsletters_listing_tabs"]');

    // step 5 - Filter by Inactive and search for the active post notification
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters#/notification/filter[status=draft]/search[' . rawurlencode($newsletterTitle) . ']');
    $i->waitForElement($this->filterChip('Status'));
    $i->waitForText('No emails found.', 15, '[data-automation-id="newsletters_listing_tabs"]');
  }

  public function searchForStandardNotificationHistoryItem(\AcceptanceTester $i) {
    $i->wantTo('Successfully search for an active notification history item');

    $newsletterSubject = 'New Post Alert [newsletter:post_title]';
    $sentNewsletterSubject = 'New Post Alert Hello Post';

    // step 1 - Prepare newsletter data
    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory->withSubject($newsletterSubject)
      ->withPostNotificationsType()
      ->withActiveStatus()
      ->withImmediateSendingSettings()
      ->create();

    (new Newsletter())
      ->withSubject($newsletterSubject)
      ->withPostNotificationHistoryType()
      ->withParent($newsletter)
      ->withSendingQueue([
        'status' => 'sent',
        'subject' => $sentNewsletterSubject,
      ])
      ->create();

    // step 2 - Search post-notification
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');
    $i->waitForListingItemsToLoad();
    $i->searchFor($newsletterSubject);
    $i->waitForText($newsletterSubject, 15, '[data-automation-id="newsletters_listing_tabs"]'); // confirm post-notification is created

    // step 3 - Search post-notification history item
    $selector = sprintf('[data-automation-id="history-%d"]', $newsletter->getId());
    $i->click($selector);
    $i->waitForListingItemsToLoad();

    $i->searchFor($newsletterSubject); // search by static newsletter subject
    $i->waitForText($sentNewsletterSubject, 15, '[data-automation-id="newsletters_listing_tabs"]');

    $i->searchFor($sentNewsletterSubject); // search by rendered post title
    $i->waitForText($sentNewsletterSubject, 15, '[data-automation-id="newsletters_listing_tabs"]');
  }
}
