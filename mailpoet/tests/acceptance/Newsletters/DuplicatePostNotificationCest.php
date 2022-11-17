<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class DuplicatePostNotificationCest {
  public function duplicatePostNotification(\AcceptanceTester $i) {
    $i->wantTo('Duplicate post notification email');

    // step 1 - Prepare post notification email
    $newsletterTitle = 'Post Notification Duplicate Test';
    $newsletterFactory = new Newsletter();
    $newsletterFactory->withSubject($newsletterTitle)
      ->withPostNotificationsType()
      ->create();

    // step 2 - Open list of post notifications
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');

    // step 3 - Duplicate post notification
    $i->waitForText($newsletterTitle);
    $i->clickItemRowActionByItemName($newsletterTitle, 'Duplicate');
    $i->waitForText('Copy of ' . $newsletterTitle);
    $i->waitForListingItemsToLoad();

    // step 5 - Open Editor
    $i->clickItemRowActionByItemName('Copy of ' . $newsletterTitle, 'Edit');
    $i->waitForElement('[data-automation-id="newsletter_title"]');
  }
}
