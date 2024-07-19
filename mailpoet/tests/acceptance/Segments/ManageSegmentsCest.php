<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\DynamicSegment;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\NewsletterLink;
use MailPoet\Test\DataFactories\StatisticsClicks;
use MailPoet\Test\DataFactories\StatisticsOpens;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoet\Test\DataFactories\User;

class ManageSegmentsCest {
  public function viewUserRoleSegmentSubscribers(\AcceptanceTester $i) {
    $i->wantTo('View WP user role segment subscribers');

    $wpAdminEmail = 'test-admin-' . rand(1, 100000) . '@example.com';
    $wpEditorEmail = 'test-editor-' . rand(1, 100000) . '@example.com';
    $wpEditorEmail2 = 'test-editor2-' . rand(1, 100000) . '@example.com';
    $wpAuthorEmail = 'test-author-' . rand(1, 100000) . '@example.com';

    $segmentTitle = 'User Role Segment Test';

    $userFactory = new User();
    $userFactory->createUser('Test Admin', 'admin', $wpAdminEmail);
    $userFactory->createUser('Test Editor', 'editor', $wpEditorEmail);
    $userFactory->createUser('Test Editor 2', 'editor', $wpEditorEmail2);
    $userFactory->createUser('Test Author', 'author', $wpAuthorEmail);

    $segmentFactory = new DynamicSegment();
    $segment = $segmentFactory
      ->withName($segmentTitle)
      ->withUserRoleFilter('Editor')
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Segments');
    $listingAutomationSelector = '[data-automation-id="mailpoet_dynamic_segment_name_' . $segment->getId() . '"]';
    $i->waitForText($segmentTitle, 10, $listingAutomationSelector);
    $i->clickWooTableActionByItemName($segmentTitle, 'View subscribers');
    $i->seeInCurrentUrl('mailpoet-subscribers#');
    $i->seeInCurrentUrl('segment=' . $segment->getId());
    $i->waitForText($wpEditorEmail, 20);
    $i->see($segmentTitle, ['css' => 'select[name=segment]']);
    $i->dontSee($wpAdminEmail);
    $i->dontSee($wpAuthorEmail);
    $i->seeNoJSErrors();

    $i->wantTo('Set pagination to 1 user per page and check if pagination is present');
    $i->click('#show-settings-link');
    $applyListingSettingsButton = '#screen-options-apply';
    $i->waitForElementClickable($applyListingSettingsButton);
    $i->fillField('#mailpoet_subscribers_per_page', '1');
    $i->click($applyListingSettingsButton);
    $i->reloadPage(); // to avoid flakyness we reload page manually
    $i->wantTo('Reorder subscribers by email and check if correct subscribes are present');
    $i->waitForElement('.mailpoet-listing-pages-next');
    $i->click('Subscriber', '[data-automation-id="listing-column-header-email"]');
    $i->waitForText($wpEditorEmail, 20);
    $i->click('.mailpoet-listing-pages-next');
    $i->waitForText($wpEditorEmail2, 20);
  }

  public function createAndEditSegment(\AcceptanceTester $i) {
    $i->wantTo('Create, edit, trash, restore and delete existing segment');

    $segmentTitle = 'User Segment';
    $segmentEditedTitle = 'Edited Segment';
    $segmentDesc = 'Lorem ipsum dolor sit amet';
    $segmentEditedDesc = 'Edited description';
    $nameElement = '[name="name"]';
    $descriptionElement = '[name="description"]';

    $userFactory = new User();
    $userFactory->createUser('Test Subscriber 1', 'subscriber', 'test-subscriber' . rand(1, 100000) . '@example.com');
    $userFactory->createUser('Test Subscriber 2', 'subscriber', 'test-subscriber' . rand(1, 100000) . '@example.com');
    $userFactory->createUser('Test Subscriber 3', 'subscriber', 'test-subscriber' . rand(1, 100000) . '@example.com');
    $userFactory->createUser('Test Editor 1', 'editor', 'test-editor' . rand(1, 100000) . '@example.com');
    $userFactory->createUser('Test Editor 2', 'editor', 'test-editor' . rand(1, 100000) . '@example.com');
    $userFactory->createUser('Test Author 1', 'author', 'test-author' . rand(1, 100000) . '@example.com');

    $i->wantTo('Create a new segment');
    $i->login();
    $i->amOnMailpoetPage('Segments');
    $i->click('[data-automation-id="new-segment"]');
    $i->waitForElement('[data-automation-id="new-custom-segment"]');
    $i->click('[data-automation-id="new-custom-segment"]');
    $i->fillField($nameElement, $segmentTitle);
    $i->fillField($descriptionElement, $segmentDesc);
    $i->selectOptionInReactSelect('WordPress user role', '[data-automation-id="select-segment-action"]');
    $i->selectOptionInReactSelect('Subscriber', '[data-automation-id="segment-wordpress-role"]');
    $i->waitForText('This segment has 3 subscribers');
    $i->waitForElementClickable('button[type="submit"]');
    $i->click('Save');
    $i->waitForNoticeAndClose('Segment successfully added!');
    $i->waitForText($segmentTitle);

    $i->wantTo('Edit existing segment');
    $i->clickWooTableActionByItemName($segmentTitle, 'Edit');
    $i->waitForText('This segment has 3 subscribers');
    $i->clearFormField($nameElement);
    $i->clearField($descriptionElement, '');
    $i->waitForElement('[value=""]' . $nameElement);
    $i->fillField($nameElement, $segmentEditedTitle);
    $i->fillField($descriptionElement, $segmentEditedDesc);
    $i->selectOptionInReactSelect('WordPress user role', '[data-automation-id="select-segment-action"]');
    $i->selectOption('[data-automation-id="segment-wordpress-role-condition"]', 'none of');
    $i->selectOptionInReactSelect('Editor', '[data-automation-id="segment-wordpress-role"]');
    $i->waitForText('This segment has 2 subscribers');
    $i->waitForElementClickable('button[type="submit"]');
    $i->click('Save');
    $i->waitForNoticeAndClose('Segment successfully updated!');
    $i->waitForText($segmentEditedTitle);
    $i->seeNoJSErrors();
  }

  public function trashAndRestoreExistingSegment(\AcceptanceTester $i) {
    $segmentFactory = new DynamicSegment();
    $segment = $segmentFactory
      ->withName('Segment 1')
      ->withUserRoleFilter('Administrator')
      ->create();
    $segmentFactory
      ->withName('Trashed Segment 2')
      ->withUserRoleFilter('Administrator')
      ->withDeleted()
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Segments');

    $i->wantTo('Trash existing segment');
    $i->clickWooTableActionInsideMoreButton($segment->getName(), 'Move to trash', 'Trash');
    $i->waitForNoticeAndClose('Segment moved to trash.');
    $i->waitForText('No data to display');
    $i->changeWooTableTab('trash');

    $i->waitForText($segment->getName());
    $i->seeNoJSErrors();

    $i->wantTo('Restore trashed segment');
    $i->clickWooTableActionInsideMoreButton($segment->getName(), 'Restore', 'Restore');
    $i->waitForNoticeAndClose('Segment restored.');
    $i->seeInCurrentURL(urlencode('group[trash]'));
    $i->changeWooTableTab('all');
    $i->waitForText($segment->getName());
    $i->seeNoJSErrors();
  }

  public function deleteExistingSegment(\AcceptanceTester $i) {
    $segmentFactory = new DynamicSegment();
    $segment1 = $segmentFactory
      ->withName('Segment 1')
      ->withUserRoleFilter('Administrator')
      ->create();
    $segment2 = $segmentFactory
      ->withName('Trashed Segment')
      ->withUserRoleFilter('Administrator')
      ->withDeleted()
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Segments');

    $i->wantTo('Trash and delete existing segment');
    $i->clickWooTableActionInsideMoreButton($segment1->getName(), 'Move to trash', 'Trash');
    $i->waitForNoticeAndClose('Segment moved to trash.');
    $i->waitForText('No data to display');
    $i->changeWooTableTab('trash');
    $i->waitForText($segment1->getName());
    $i->clickWooTableActionInsideMoreButton($segment1->getName(), 'Delete permanently', 'Delete permanently');
    $i->waitForNoticeAndClose('Segment permanently deleted.');
    $i->seeNoJSErrors();
  }

  public function createEmailMachineOpenedSegment(\AcceptanceTester $i) {
    $i->wantTo('Create a new email machine opened segment');

    $emailSubject = 'Segment Email Machine Opened Test';
    $newsletter = (new Newsletter())
      ->withSendingQueue()->withSubject($emailSubject)->withSentStatus()->create();

    $segmentTitle = 'Email Segment Machine Opened Test';
    $segmentDesc = 'Lorem ipsum dolor sit amet';

    $subscriber1 = (new Subscriber())
      ->withEmail('stats_test1@example.com')
      ->create();
    $subscriber2 = (new Subscriber())
      ->withEmail('stats_test2@example.com')
      ->create();
    (new StatisticsOpens($newsletter, $subscriber1))->withMachineUserAgentType()->create();
    (new StatisticsOpens($newsletter, $subscriber2))->withMachineUserAgentType()->create();

    $i->login();
    $i->amOnMailpoetPage('Segments');
    $i->click('[data-automation-id="new-segment"]');
    $i->waitForElement('[data-automation-id="new-custom-segment"]');
    $i->click('[data-automation-id="new-custom-segment"]');
    $i->fillField(['name' => 'name'], $segmentTitle);
    $i->fillField(['name' => 'description'], $segmentDesc);
    $i->selectOptionInReactSelect('machine-opened', '[data-automation-id="select-segment-action"]');
    $i->selectOptionInReactSelect($emailSubject, '[data-automation-id="segment-email"]');
    $i->selectOption('[data-automation-id="segment-email-opens-condition"]', 'any of');
    $i->waitForText('This segment has 2 subscribers');
    $i->selectOption('[data-automation-id="segment-email-opens-condition"]', 'all of');
    $i->waitForText('This segment has 2 subscribers');
    $i->waitForElementClickable('button[type="submit"]');
    $i->click('Save');
    $i->amOnMailpoetPage('Segments');
    $i->waitForText($segmentTitle, 20);
  }

  public function createEmailClickedSegment(\AcceptanceTester $i) {
    $i->wantTo('Create a new email clicked segment');

    $segmentTitle = 'Email Clicked Segment Test';
    $segmentDesc = 'Lorem ipsum dolor sit amet';

    $emailSubject = 'Segment Email Clicked Test';
    $newsletter = (new Newsletter())
      ->withSendingQueue()->withSubject($emailSubject)->withSentStatus()->create();
    $this->createClickInNewsletter($newsletter);

    $i->login();
    $i->amOnMailpoetPage('Segments');
    $i->click('[data-automation-id="new-segment"]');
    $i->waitForElement('[data-automation-id="new-custom-segment"]');
    $i->click('[data-automation-id="new-custom-segment"]');
    $i->fillField(['name' => 'name'], $segmentTitle);
    $i->fillField(['name' => 'description'], $segmentDesc);
    $i->selectOptionInReactSelect('clicked', '[data-automation-id="select-segment-action"]');
    $i->selectOptionInReactSelect($emailSubject, '[data-automation-id="segment-email"]');
    $i->selectOption('[data-automation-id="select-operator"]', 'none of');
    $i->waitForText('This segment has 0 subscribers');
    $i->selectOption('[data-automation-id="select-operator"]', 'any of');
    $i->waitForText('This segment has 1 subscribers');
    $i->waitForElementClickable('button[type="submit"]');
    $i->click('Save');
    $i->amOnMailpoetPage('Segments');
    $i->waitForText($segmentTitle, 20);
  }

  public function bulkTrashRestoreAndDeleteSegments(\AcceptanceTester $i) {
    $i->wantTo('Create bulk trash restore and delete segments');

    $segmentFactory = new DynamicSegment();
    $segment1Name = 'Segment 1';
    $segment2Name = 'Segment 2';
    $segment1 = $segmentFactory
      ->withName($segment1Name)
      ->withUserRoleFilter('Editor')
      ->withDeleted()
      ->create();
    $segment2 = $segmentFactory
      ->withName($segment2Name)
      ->withUserRoleFilter('Editor')
      ->withDeleted()
      ->create();

    $i->login();

    $i->wantTo('Select trashed segments one by one and bulk restore them');
    $i->amOnMailpoetPage('Segments');
    $i->changeWooTableTab('trash');
    $i->waitForText($segment1Name);
    $i->checkWooTableCheckboxForItemName($segment1Name);
    $i->checkWooTableCheckboxForItemName($segment2Name);
    $i->selectOption('Bulk actions', 'Restore');
    $i->click('Restore'); // confirmation modal
    $i->wantTo('Check that segments were restored and trash filter is not present');
    $i->waitForText('No data to display');
    $i->changeWooTableTab('all');
    $i->waitForText($segment1Name);
    $i->waitForText($segment2Name);

    $i->wantTo('Select all segments and move them back to trash');
    $i->click('[data-automation-id="select_all"]');
    $i->selectOption('Bulk actions', 'Trash');
    $i->waitForText('Are you sure you want to trash the selected segments');
    $i->click(['xpath' => '//button[text()="Trash"]']); // confirmation modal, xpath to avoid clicking the Trash tab
    $i->waitForText('No data to display');

    $i->wantTo('Select all segments in trash and bulk delete them permanently');
    $i->changeWooTableTab('trash');
    $i->waitForText($segment1Name);
    $i->click('[data-automation-id="select_all"]');
    $i->selectOption('Bulk actions', 'Delete permanently');
    $i->click('Delete permanently'); // modal confirmation
    $i->waitForText('No data to display');
  }

  public function cantTrashOrBulkTrashActivelyUsedSegment(\AcceptanceTester $i) {
    $segmentTitle = 'Active Segment';
    $subject = 'Post notification';
    $segmentFactory = new DynamicSegment();
    $segment = $segmentFactory
      ->withName($segmentTitle)
      ->create();
    $newsletterFactory = new Newsletter();
    $newsletterFactory->withPostNotificationsType()
      ->withSegments([$segment])
      ->withSubject($subject)
      ->create();

    $i->wantTo('Check that user can’t delete actively used list');
    $i->login();
    $i->amOnMailpoetPage('Segments');
    $i->waitForText($segmentTitle, 5, '[data-automation-id="mailpoet_dynamic_segment_name_' . $segment->getId() . '"]');
    $i->clickWooTableActionInsideMoreButton($segmentTitle, 'Move to trash');
    $i->click(['xpath' => '//button[text()="Trash"]']); // confirmation modal, xpath to avoid clicking the Trash tab
    $i->waitForText("Segment '{$segmentTitle}' cannot be deleted because it’s used for '{$subject}' email");
    $i->seeNoJSErrors();
    $i->checkWooTableCheckboxForItemName($segment->getName());
    $i->selectOption('Bulk actions', 'Trash');
    $i->click(['xpath' => '//button[text()="Trash"]']); // confirmation modal, xpath to avoid clicking the Trash tab
    $i->waitForText("Segment '{$segmentTitle}' cannot be deleted because it’s used for '{$subject}' email");
  }

  public function createUserSegmentAndCheckCount(\AcceptanceTester $i) {
    $i->wantTo('Create a new user segment');

    $userFactory = new User();
    $userFactory->createUser('Test User 1', 'editor', 'test-editor' . rand(1, 100000) . '@example.com');
    $userFactory->createUser('Test User 2', 'editor', 'test-editor' . rand(1, 100000) . '@example.com');
    $segmentTitle = 'Create Editors Dynamic Segment Test';
    $segmentDesc = 'Lorem ipsum dolor sit amet';

    $i->login();
    $i->amOnMailpoetPage('Segments');
    $i->click('[data-automation-id="new-segment"]');
    $i->waitForElement('[data-automation-id="new-custom-segment"]');
    $i->click('[data-automation-id="new-custom-segment"]');
    $i->fillField(['name' => 'name'], $segmentTitle);
    $i->fillField(['name' => 'description'], $segmentDesc);
    $i->selectOptionInReactSelect('WordPress user role', '[data-automation-id="select-segment-action"]');
    $i->selectOptionInReactSelect('Editor', '[data-automation-id="segment-wordpress-role"]');
    $i->waitForText('This segment has 2 subscribers.');
    $i->seeNoJSErrors();
    $i->click('Save');
    $i->amOnMailpoetPage('Segments');
    $i->waitForText($segmentTitle, 20);
  }

  public function updateUserSegmentAndCheckCount(\AcceptanceTester $i) {
    $i->wantTo('Update a user segment');

    $segmentTitle = 'Update Authors Dynamic Segment Test';

    $userFactory = new User();
    $userFactory->createUser('Test Author 1', 'author', 'test-author' . rand(1, 100000) . '@example.com');
    $userFactory->createUser('Test Author 2', 'author', 'test-author' . rand(1, 100000) . '@example.com');
    $userFactory->createUser('Test Subscriber 1', 'subscriber', 'test-subscriber' . rand(1, 100000) . '@example.com');

    $segmentFactory = new DynamicSegment();
    $segment = $segmentFactory
      ->withName($segmentTitle)
      ->withUserRoleFilter('Author')
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Segments');
    $listingAutomationSelector = '[data-automation-id="mailpoet_dynamic_segment_name_' . $segment->getId() . '"]';
    $i->waitForText($segmentTitle, 10, $listingAutomationSelector);
    $i->clickWooTableActionByItemName($segmentTitle, 'Edit');
    $i->waitForElementNotVisible('.mailpoet_form_loading');
    $i->waitForText('This segment has 2 subscribers.');
    $i->seeNoJSErrors();
    $i->selectOptionInReactSelect('Subscriber', '[data-automation-id="segment-wordpress-role"]');
    $i->waitForText('This segment has 1 subscribers.');
    $i->seeNoJSErrors();
    $i->click('Save');
    $i->amOnMailpoetPage('Segments');
    $i->waitForText($segmentTitle, 20);
  }

  public function tryCreatingDynamicSegmentWithMoreFilters(\AcceptanceTester $i) {
    $i->wantTo('Create a new Dynamic Segment with 2 Filters');

    $filterRowOne = '[data-automation-id="filter-row-0"]';
    $actionSelectElement = '[data-automation-id="select-segment-action"]';
    $roleSelectElement = '[data-automation-id="segment-wordpress-role"]';
    $segmentTitle = 'Segment Two Filters';
    $segmentDesc = 'Segment description';

    $i->login();
    $i->amOnMailpoetPage('Segments');
    $i->click('[data-automation-id="new-segment"]');
    $i->waitForElement('[data-automation-id="new-custom-segment"]');
    $i->click('[data-automation-id="new-custom-segment"]');
    $i->fillField(['name' => 'name'], $segmentTitle);
    $i->fillField(['name' => 'description'], $segmentDesc);
    $i->selectOptionInReactSelect('WordPress user role', "{$filterRowOne} {$actionSelectElement}");
    $i->selectOptionInReactSelect('Admin', "{$filterRowOne} {$roleSelectElement}");
    $i->click('Add a condition');
    $i->see('Multiple conditions per segment are not available in the free version of the MailPoet plugin');
    $i->seeNoJSErrors();
  }

  public function createSegmentFromTemplate(\AcceptanceTester $i) {
    $i->wantTo('Create a segment from a template');
    $i->login();
    $i->amOnMailpoetPage('Segments');
    $i->click('[data-automation-id="new-segment"]');
    $i->selectSegmentTemplate('Recently Subscribed');
    $i->waitForElementVisible('[data-automation-id="input-name"]');
    $i->seeInField('[data-automation-id="input-name"]', 'Recently Subscribed');
    $i->click('#mailpoet-segments-back-button');
    $i->waitForText('Recently Subscribed');
    $i->seeNoJSErrors();
  }

  private function createClickInNewsletter($newsletter) {
    $subscriber = (new Subscriber())->create();
    $newsletterLink = (new NewsletterLink($newsletter))->create();
    return (new StatisticsClicks($newsletterLink, $subscriber))->create();
  }
}
