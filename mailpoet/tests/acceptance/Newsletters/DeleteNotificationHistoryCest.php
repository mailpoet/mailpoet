<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class DeleteNotificationHistoryCest {
  public function delete(\AcceptanceTester $i) {
    // step 1 - Prepare data
    $i->wantTo('delete a notification history');
    $newsletterName = 'Post notification history';
    $postNotification = (new Newsletter())
      ->withSubject('Deletion Test Post Notification History')
      ->withPostNotificationsType()
      ->create();
    $postNotificationHistory = (new Newsletter())
      ->withSubject($newsletterName)
      ->withPostNotificationHistoryType()
      ->withParent($postNotification)
      ->create();
    // step 2 - Open list
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');
    $i->waitForElement('[data-automation-id="history-' . $postNotification->getId() . '"]');
    $i->click('[data-automation-id="history-' . $postNotification->getId() . '"]');
    //step 3 - Delete Notification
    $i->waitForElement('[data-automation-id="listing_item_' . $postNotificationHistory->getId() . '"]');
    $i->clickItemRowActionByItemName($newsletterName, 'Move to trash');
    $i->changeGroupInListingFilter('trash');
    $i->waitForText($newsletterName);
    //step 4 - Restore Notification
    $i->clickItemRowActionByItemName($newsletterName, 'Restore');
    $i->waitForElementNotVisible('[data-automation-id="filters_trash"]');
    $i->waitForText($newsletterName);
    //step 4 - Delete permanently Notification
    $i->clickItemRowActionByItemName($newsletterName, 'Move to trash');
    $i->changeGroupInListingFilter('trash');
    $i->waitForText($newsletterName);
    $i->clickItemRowActionByItemName($newsletterName, 'Delete Permanently');
    $i->waitForText('permanently deleted.');
    $i->waitForElementNotVisible($newsletterName);
  }
}
