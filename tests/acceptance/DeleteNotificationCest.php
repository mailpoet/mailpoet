<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class DeleteNotificationCest {

  public function deleteNotification(\AcceptanceTester $I) {
    // step 1 - Prepare post notification data
    $I->wantTo('delete a notification');
    $newsletter_name = 'Deletion Test Post Notification';
    $factory = new Newsletter();
    $factory->withSubject($newsletter_name)
      ->withPostNotificationsType()
      ->create();
    // step 2 - Open list of post notifications
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');
    //step 3 - Delete Notification
    $I->waitForText($newsletter_name);
    $I->clickItemRowActionByItemName($newsletter_name, 'Move to trash');
    $I->waitForElement('[data-automation-id="filters_trash"]');
    $I->click('[data-automation-id="filters_trash"]');
    $I->waitForText($newsletter_name);
  }

  public function restoreNotificationFromTrash(\AcceptanceTester $I) {
    // step 1 - Prepare post notification data
    $I->wantTo('Restore a newsletter from trash');
    $newsletter_name = 'Restore from Trash Test Post Notification';
    $factory = new Newsletter();
    $factory->withSubject($newsletter_name)
      ->withPostNotificationsType()
      ->withDeleted()
      ->create();
    // step 2 - Open list of post notifications
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');
    //step 3 - Restore notification from trash
    $I->waitForElement('[data-automation-id="filters_trash"]');
    $I->click('[data-automation-id="filters_trash"]');
    $I->waitForText($newsletter_name);
    $I->clickItemRowActionByItemName($newsletter_name, 'Restore');
    $I->amOnMailpoetPage('Emails');
    $I->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForText($newsletter_name);
  }

  public function deleteNotificationPermanently(\AcceptanceTester $I) {
    // step 1 - Prepare post notification data
    $I->wantTo('Permanently delete a notification');
    $newsletter_name = 'Goodbye Forever Notification Test';
    $factory = new Newsletter();
    $factory->withSubject($newsletter_name)
      ->withPostNotificationsType()
      ->withDeleted()
      ->create();
    // step 2 - Open list of post notifications
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');
    // step 3 - goodbye forever, notification
    $I->waitForElement('[data-automation-id="filters_trash"]');
    $I->click('[data-automation-id="filters_trash"]');
    $I->waitForText($newsletter_name);
    $I->clickItemRowActionByItemName($newsletter_name, 'Delete Permanently');
    $I->waitForText('permanently deleted.');
    $I->waitForElementNotVisible($newsletter_name);
  }
}
