<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class DeleteNotificationHistoryCest {

  function delete(\AcceptanceTester $I) {
    // step 1 - Prepare data
    $I->wantTo('delete a notification history');
    $newsletter_name = 'Post notification history';
    $post_notification = (new Newsletter())
      ->withSubject('Deletion Test Post Notification History')
      ->withPostNotificationsType()
      ->create();
    $post_notification_history = (new Newsletter())
      ->withSubject($newsletter_name)
      ->withPostNotificationHistoryType()
      ->withParentId($post_notification->id)
      ->create();
    // step 2 - Open list
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForElement('[data-automation-id="history-' . $post_notification->id . '"]');
    $I->click('[data-automation-id="history-' . $post_notification->id . '"]');
    //step 3 - Delete Notification
    $I->waitForElement('[data-automation-id="listing_item_' . $post_notification_history->id . '"]');
    $I->clickItemRowActionByItemName($newsletter_name, 'Move to trash');
    $I->waitForElement('[data-automation-id="filters_trash"]');
    $I->click('[data-automation-id="filters_trash"]');
    $I->waitForText($newsletter_name);
    //step 4 - Restore Notification
    $I->clickItemRowActionByItemName($newsletter_name, 'Restore');
    $I->waitForElementNotVisible('[data-automation-id="filters_trash"]');
    $I->waitForText($newsletter_name);
    //step 4 - Delete permanently Notification
    $I->clickItemRowActionByItemName($newsletter_name, 'Move to trash');
    $I->waitForElement('[data-automation-id="filters_trash"]');
    $I->click('[data-automation-id="filters_trash"]');
    $I->waitForText($newsletter_name);
    $I->clickItemRowActionByItemName($newsletter_name, 'Delete Permanently');
    $I->waitForText('permanently deleted.');
    $I->waitForElementNotVisible($newsletter_name);
  }

}
