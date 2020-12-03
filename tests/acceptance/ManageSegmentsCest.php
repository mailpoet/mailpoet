<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\DynamicSegment;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\User;

class ManageSegmentsCest {
  public function _before() {
    (new Settings())->withWooCommerceListImportPageDisplayed(true);
    (new Settings())->withCookieRevenueTrackingDisabled();
  }

  public function viewUserRoleSegmentSubscribers(\AcceptanceTester $i) {
    $i->wantTo('View WP user role segment subscribers');

    $wpAdminEmail = 'test-admin@example.com';
    $wpEditorEmail = 'test-editor@example.com';
    $wpAuthorEmail = 'test-author@example.com';
    $segmentTitle = 'User Role Segment Test';

    $userFactory = new User();
    $userFactory->createUser('Test Admin', 'admin', $wpAdminEmail);
    $userFactory->createUser('Test Editor', 'editor', $wpEditorEmail);
    $userFactory->createUser('Test Author', 'author', $wpAuthorEmail);

    $segmentFactory = new DynamicSegment();
    $segment = $segmentFactory
      ->withName($segmentTitle)
      ->withUserRoleFilter('Editor')
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $listingAutomationSelector = '[data-automation-id="listing_item_' . $segment->id . '"]';
    $i->waitForText($segmentTitle, 10, $listingAutomationSelector);
    $i->clickItemRowActionByItemName($segmentTitle, 'View Subscribers');
    $i->seeInCurrentUrl('mailpoet-subscribers#');
    $i->seeInCurrentUrl('segment=' . $segment->id);
    $i->waitForText($wpEditorEmail, 20);
    $i->see($segmentTitle, ['css' => 'select[name=segment]']);
    $i->dontSee($wpAdminEmail);
    $i->dontSee($wpAuthorEmail);
    $i->seeNoJSErrors();
  }

  public function createEditTrashRestoreAndDeleteExistingSegment(\AcceptanceTester $i) {
    $i->wantTo('Create, edit, trash, restore and delete existing segment');
    $segmentTitle = 'User Segment';
    $segmentEditedTitle = 'User Segment EDITED';
    $segmentDesc = 'Lorem ipsum dolor sit amet';
    $segmentEditedDesc = 'Lorem ipsum dolor sit amet EDITED';
    
    // Prepare (2) additional segments for the test
    $segmentFactory = new DynamicSegment();
    $segment = $segmentFactory
      ->withName($segmentTitle . ' TRASHED 1')
      ->withUserRoleFilter('Administrator')
      ->withDeleted()
      ->create();
      $segmentFactory
      ->withName($segmentTitle . ' TRASHED 2')
      ->withUserRoleFilter('Administrator')
      ->withDeleted()
      ->create();
    
    // Create a new segment
    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->click('[data-automation-id="new-segment"]');
    $i->fillField(['name' => 'name'], $segmentTitle);
    $i->fillField(['name' => 'description'], $segmentDesc);
    $i->selectOption('form select[name=segmentType]', 'WordPress user roles');
    $i->selectOption('form select[name=wordpressRole]', 'Subscriber');
    $i->click('Save');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText($segmentTitle);

    // Edit existing segment
    $i->clickItemRowActionByItemName($segmentTitle, 'Edit');
    $i->waitForElementNotVisible('.mailpoet_form_loading');
    $i->fillField(['name' => 'name'], $segmentEditedTitle);
    $i->fillField(['name' => 'description'], $segmentEditedDesc);
    $i->selectOption('form select[name=segmentType]', 'WordPress user roles');
    $i->selectOption('form select[name=wordpressRole]', 'Editor');
    $i->click('Save');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText($segmentEditedTitle);
    $i->seeNoJSErrors();

    // Trash existing segment
    $i->clickItemRowActionByItemName($segmentEditedTitle, 'Move to trash');
    $i->waitForText('1 segment was moved to the trash.');
    $i->click('[data-automation-id="filters_trash"]');
    $i->waitForText($segmentEditedTitle);
    $i->seeNoJSErrors();

    // Restore trashed segment
    $i->clickItemRowActionByItemName($segmentEditedTitle, 'Restore');
    $i->waitForText('1 segment has been restored from the Trash.');
    $i->seeInCurrentURL(urlencode('group[trash]'));
    $i->click('[data-automation-id="filters_all"]');
    $i->waitForText($segmentEditedTitle);
    $i->seeNoJSErrors();
    
    // Trash and delete existing segment
    $i->clickItemRowActionByItemName($segmentEditedTitle, 'Move to trash');
    $i->waitForText('1 segment was moved to the trash.');
    $i->click('[data-automation-id="filters_trash"]');
    $i->waitForText($segmentEditedTitle);
    $i->clickItemRowActionByItemName($segmentEditedTitle, 'Delete permanently');
    $i->waitForText('1 segment was permanently deleted.');
    $i->seeNoJSErrors();
    $i->waitForText($segmentTitle . ' TRASHED 1');
    $i->waitForText($segmentTitle . ' TRASHED 2');

    // Empty trash from other (2) segments
    $i->click('[data-automation-id="empty_trash"]');
    $i->waitForText('2 segments were permanently deleted.');
    $listingAutomationSelector = '[data-automation-id="listing_item_' . $segment->id . '"]';
    $i->dontSeeElement($listingAutomationSelector);
    $i->seeInCurrentURL(urlencode('group[all]'));
    $i->seeNoJSErrors();
  }

  public function createEmailSegment(\AcceptanceTester $i) {
    $i->wantTo('Create a new email segment');
    $emailSubject = 'Segment Email Test';
    $newsletterFactory = new Newsletter();
    $newsletterFactory->withSubject($emailSubject)->create();
    $segmentTitle = 'Create Email Segment Test';
    $segmentDesc = 'Lorem ipsum dolor sit amet';
    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->click('[data-automation-id="new-segment"]');
    $i->fillField(['name' => 'name'], $segmentTitle);
    $i->fillField(['name' => 'description'], $segmentDesc);
    $i->selectOption('form select[name=segmentType]', 'Email');
    $i->selectOption('form select[name=action]', 'opened');
    $i->click('#select2-newsletter_id-container');
    $i->selectOptionInSelect2($emailSubject);
    $i->click('Save');
    $i->amOnMailpoetPage('Lists');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText($segmentTitle, 20);
  }
}
