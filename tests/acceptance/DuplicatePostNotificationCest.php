<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class DuplicatePostNotificationCest {

  public function duplicatePostNotification(\AcceptanceTester $I) {
    $I->wantTo('Duplicate post notification email');

    // step 1 - Prepare post notification email
    $newsletter_title = 'Post Notification Duplicate Test';
    $newsletter_factory = new Newsletter();
    $newsletter_factory->withSubject($newsletter_title)
      ->withPostNotificationsType()
      ->create();

    // step 2 - Open list of post notifications
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');

    // step 3 - Duplicate post notification
    $I->waitForText($newsletter_title);
    $I->clickItemRowActionByItemName($newsletter_title, 'Duplicate');
    $I->waitForText('Copy of ' . $newsletter_title);
    $I->waitForListingItemsToLoad();

    // step 5 - Open Editor
    $I->clickItemRowActionByItemName('Copy of ' . $newsletter_title, 'Edit');
    $I->waitForElement('[data-automation-id="newsletter_title"]');
  }

}
