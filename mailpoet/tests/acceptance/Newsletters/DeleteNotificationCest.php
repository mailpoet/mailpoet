<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class DeleteNotificationCest {
  public function deleteNotification(\AcceptanceTester $i) {
    // step 1 - Prepare post notification data
    $i->wantTo('delete a notification');
    $newsletterName = 'Deletion Test Post Notification';
    $factory = new Newsletter();
    $factory->withSubject($newsletterName)
      ->withPostNotificationsType()
      ->create();
    // step 2 - Open list of post notifications
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');
    //step 3 - Delete Notification
    $i->waitForText($newsletterName);
    $i->clickItemRowActionByItemName($newsletterName, 'Move to trash');
    $i->waitForElement('[data-automation-id="filters_trash"]');
    $i->click('[data-automation-id="filters_trash"]');
    $i->waitForText($newsletterName);
  }

  public function restoreNotificationFromTrash(\AcceptanceTester $i) {
    // step 1 - Prepare post notification data
    $i->wantTo('Restore a newsletter from trash');
    $newsletterName = 'Restore from Trash Test Post Notification';
    $factory = new Newsletter();
    $factory->withSubject($newsletterName)
      ->withPostNotificationsType()
      ->withDeleted()
      ->create();
    // step 2 - Open list of post notifications
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');
    //step 3 - Restore notification from trash
    $i->waitForElement('[data-automation-id="filters_trash"]');
    $i->click('[data-automation-id="filters_trash"]');
    $i->waitForText($newsletterName);
    $i->clickItemRowActionByItemName($newsletterName, 'Restore');
    $i->amOnMailpoetPage('Emails');
    $i->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');
    $i->waitForText($newsletterName);
  }

  public function deleteNotificationPermanently(\AcceptanceTester $i) {
    // step 1 - Prepare post notification data
    $i->wantTo('Permanently delete a notification');
    $newsletterName = 'Goodbye Forever Notification Test';
    $factory = new Newsletter();
    $factory->withSubject($newsletterName)
      ->withPostNotificationsType()
      ->withDeleted()
      ->create();
    // step 2 - Open list of post notifications
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');
    // step 3 - goodbye forever, notification
    $i->waitForElement('[data-automation-id="filters_trash"]');
    $i->click('[data-automation-id="filters_trash"]');
    $i->waitForText($newsletterName);
    $i->clickItemRowActionByItemName($newsletterName, 'Delete Permanently');
    $i->waitForText('permanently deleted.');
    $i->waitForElementNotVisible($newsletterName);
  }
}
