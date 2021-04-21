<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\DynamicSegment;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\User;
use MailPoet\Test\DataFactories\WooCommerceProduct;

class ManageSegmentsCest {
  public function _before() {
    (new Settings())->withWooCommerceListImportPageDisplayed(true);
    (new Settings())->withCookieRevenueTrackingDisabled();
  }

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
    $i->amOnMailpoetPage('Lists');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $listingAutomationSelector = '[data-automation-id="listing_item_' . $segment->getId() . '"]';
    $i->waitForText($segmentTitle, 10, $listingAutomationSelector);
    $i->clickItemRowActionByItemName($segmentTitle, 'View Subscribers');
    $i->seeInCurrentUrl('mailpoet-subscribers#');
    $i->seeInCurrentUrl('segment=' . $segment->getId());
    $i->waitForText($wpEditorEmail, 20);
    $i->see($segmentTitle, ['css' => 'select[name=segment]']);
    $i->dontSee($wpAdminEmail);
    $i->dontSee($wpAuthorEmail);
    $i->seeNoJSErrors();

    $i->wantTo('Set pagination to 1 user per page and check if pagination is present');
    $i->click('#show-settings-link');
    $i->fillField('#mailpoet_subscribers_per_page', '1');
    $i->click('#screen-options-apply');
    $i->wait(2); // to avoid flakyness, required to wait a bit
    $i->wantTo('Reorder subscribers by email and check if correct subscribes are present');
    $i->waitForElement('.mailpoet-listing-pages-next');
    $i->click('Subscriber', '[data-automation-id="listing-column-header-email"]');
    $i->waitForText($wpEditorEmail, 20);
    $i->click('.mailpoet-listing-pages-next');
    $i->waitForText($wpEditorEmail2, 20);
  }

  public function createEditTrashRestoreAndDeleteExistingSegment(\AcceptanceTester $i) {
    $i->wantTo('Create, edit, trash, restore and delete existing segment');
    $segmentTitle = 'User Segment';
    $segmentEditedTitle = 'User Segment EDITED';
    $segmentDesc = 'Lorem ipsum dolor sit amet';
    $segmentEditedDesc = 'Lorem ipsum dolor sit amet EDITED';

    $i->wantTo('Prepare (2) additional segments for the test');

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

    $i->wantTo('Create a new segment');

    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->click('[data-automation-id="new-segment"]');
    $i->fillField(['name' => 'name'], $segmentTitle);
    $i->fillField(['name' => 'description'], $segmentDesc);
    $i->fillField(['name' => 'description'], $segmentDesc);
    $i->selectOptionInReactSelect('has WordPress user role', '[data-automation-id="select-segment-action"]');
    $i->selectOptionInReactSelect('Subscriber', '[data-automation-id="segment-wordpress-role"]');
    $i->click('Save');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText($segmentTitle);

    $i->wantTo('Edit existing segment');
    $i->clickItemRowActionByItemName($segmentTitle, 'Edit');
    $i->waitForElementNotVisible('.mailpoet_form_loading');
    $i->clearField('#field_name');
    $i->fillField(['name' => 'name'], $segmentEditedTitle);
    $i->fillField(['name' => 'description'], $segmentEditedDesc);
    $i->selectOptionInReactSelect('has WordPress user role', '[data-automation-id="select-segment-action"]');
    $i->selectOptionInReactSelect('Editor', '[data-automation-id="segment-wordpress-role"]');
    $i->click('Save');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText($segmentEditedTitle);
    $i->seeNoJSErrors();

    $i->wantTo('Trash existing segment');
    $i->clickItemRowActionByItemName($segmentEditedTitle, 'Move to trash');
    $i->waitForText('1 segment was moved to the trash.');
    $i->click('[data-automation-id="filters_trash"]');
    $i->waitForText($segmentEditedTitle);
    $i->seeNoJSErrors();

    $i->wantTo('Restore trashed segment');
    $i->clickItemRowActionByItemName($segmentEditedTitle, 'Restore');
    $i->waitForText('1 segment has been restored from the Trash.');
    $i->seeInCurrentURL(urlencode('group[trash]'));
    $i->click('[data-automation-id="filters_all"]');
    $i->waitForText($segmentEditedTitle);
    $i->seeNoJSErrors();

    $i->wantTo('Trash and delete existing segment');

    $i->clickItemRowActionByItemName($segmentEditedTitle, 'Move to trash');
    $i->waitForText('1 segment was moved to the trash.');
    $i->waitForElementClickable('[data-automation-id="filters_trash"]');
    $i->click('[data-automation-id="filters_trash"]');
    $i->waitForText($segmentEditedTitle);
    $i->clickItemRowActionByItemName($segmentEditedTitle, 'Delete permanently');
    $i->waitForText('1 segment was permanently deleted.');
    $i->seeNoJSErrors();
    $i->waitForText($segmentTitle . ' TRASHED 1');
    $i->waitForText($segmentTitle . ' TRASHED 2');

    $i->wantTo('Empty trash from other (2) segments');
    $i->click('[data-automation-id="empty_trash"]');
    $i->waitForText('2 segments were permanently deleted.');
    $listingAutomationSelector = '[data-automation-id="listing_item_' . $segment->getId() . '"]';
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
    $i->selectOptionInReactSelect('opened', '[data-automation-id="select-segment-action"]');
    $i->selectOptionInReactSelect($emailSubject, '[data-automation-id="segment-email"]');
    $i->click('Save');
    $i->amOnMailpoetPage('Lists');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
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
    $i->amOnMailpoetPage('Lists');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForElement('[data-automation-id="filters_trash"]');
    $i->click('[data-automation-id="filters_trash"]');
    $i->waitForText($segment1Name);
    $i->checkOption('[data-automation-id="listing-row-checkbox-' . $segment1->getId() . '"]');
    $i->checkOption('[data-automation-id="listing-row-checkbox-' . $segment2->getId() . '"]');
    $i->waitForText('Restore');
    $i->click('Restore');
    $i->wantTo('Check that segments were resrored and trash filter is not present');
    $i->waitForElementNotVisible('[data-automation-id="filters_trash"]');
    $i->waitForText($segment1Name);
    $i->waitForText($segment2Name);

    $i->wantTo('Select all segments and move them back to trash');
    $i->waitForElement('[data-automation-id="filters_all"]');
    $i->waitForText($segment1Name);
    $i->click('[data-automation-id="select_all"]');
    $i->waitForText('Move to trash');
    $i->click('Move to trash');
    $i->waitForText('No segments found');

    $i->wantTo('Select all segments in trash and bulk delete them permanently');
    $i->waitForElement('[data-automation-id="filters_trash"]');
    $i->click('[data-automation-id="filters_trash"]');
    $i->waitForElement('[data-automation-id="select_all"]');
    $i->click('[data-automation-id="select_all"]');
    $i->waitForText('Delete permanently');
    $i->click('Delete permanently');
    $i->waitForText('No segments found');
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
    $i->amOnMailpoetPage('Lists');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForElement('[data-automation-id="filters_all"]');
    $i->waitForText($segmentTitle, 5, '[data-automation-id="listing_item_' . $segment->getId() . '"]');
    $i->clickItemRowActionByItemName($segmentTitle, 'Move to trash');
    $i->waitForText("Segment cannot be deleted because it’s used for '{$subject}' email");
    $i->seeNoJSErrors();
    $i->checkOption('[data-automation-id="listing-row-checkbox-' . $segment->getId() . '"]');
    $i->waitForText('Move to trash');
    $i->click('Move to trash');
    $i->waitForText('0 segments were moved to the trash.');
  }

  public function createUserSegmentAndCheckCount(\AcceptanceTester $i) {
    $userFactory = new User();
    $userFactory->createUser('Test User 1', 'editor', 'test-editor' . rand(1, 100000) . '@example.com');
    $userFactory->createUser('Test User 2', 'editor', 'test-editor' . rand(1, 100000) . '@example.com');
    $i->wantTo('Create a new user segment');
    $segmentTitle = 'Create Editors Dynamic Segment Test';
    $segmentDesc = 'Lorem ipsum dolor sit amet';
    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->click('[data-automation-id="new-segment"]');
    $i->fillField(['name' => 'name'], $segmentTitle);
    $i->fillField(['name' => 'description'], $segmentDesc);
    $i->selectOptionInReactSelect('has WordPress user role', '[data-automation-id="select-segment-action"]');
    $i->selectOptionInReactSelect('Editor', '[data-automation-id="segment-wordpress-role"]');
    $i->waitForText('Calculating segment size…');
    $i->waitForText('This segment has 2 subscribers.');
    $i->seeNoJSErrors();
    $i->click('Save');
    $i->amOnMailpoetPage('Lists');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText($segmentTitle, 20);
  }

  public function updateUserSegmentAndCheckCount(\AcceptanceTester $i) {
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

    $i->wantTo('Update a user segment');
    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $listingAutomationSelector = '[data-automation-id="listing_item_' . $segment->getId() . '"]';
    $i->waitForText($segmentTitle, 10, $listingAutomationSelector);
    $i->clickItemRowActionByItemName($segmentTitle, 'Edit');
    $i->waitForElementNotVisible('.mailpoet_form_loading');
    $i->waitForText('This segment has 2 subscribers.');
    $i->seeNoJSErrors();
    $i->selectOptionInReactSelect('Subscriber', '[data-automation-id="segment-wordpress-role"]');
    $i->waitForText('This segment has 1 subscribers.');
    $i->seeNoJSErrors();
    $i->click('Save');
    $i->amOnMailpoetPage('Lists');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText($segmentTitle, 20);
  }

  public function createAndEditWooCommercePurchasedInCategorySegment(\AcceptanceTester $i) {
    $i->activateWooCommerce();
    $productFactory = new WooCommerceProduct($i);
    $category1Id = $productFactory->createCategory('Category 1');
    $category2Id = $productFactory->createCategory('Category 2');
    $productFactory->withCategoryIds([$category1Id, $category2Id])->create();
    $categorySelectElement = '[data-automation-id="select-segment-category"]';
    $actionSelectElement = '[data-automation-id="select-segment-action"]';

    $i->wantTo('Create a new WooCommerce purchased in category segment');
    $segmentTitle = 'Segment Woo Category Test';
    $segmentDesc = 'Segment description';
    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->click('[data-automation-id="new-segment"]');
    $i->fillField(['name' => 'name'], $segmentTitle);
    $i->fillField(['name' => 'description'], $segmentDesc);
    $i->selectOptionInReactSelect('purchased in this category', $actionSelectElement);
    $i->waitForElement($categorySelectElement);
    $i->selectOptionInReactSelect('Category 2', $categorySelectElement);
    $i->click('Save');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText($segmentTitle);

    $i->wantTo('Open edit form and check that all values were saved correctly');
    $i->clickItemRowActionByItemName($segmentTitle, 'Edit');
    $i->waitForElement($categorySelectElement);
    $i->seeInField(['name' => 'name'], $segmentTitle);
    $i->seeInField(['name' => 'description'], $segmentDesc);
    $i->see('purchased in this category', $actionSelectElement);
    $i->see('Category 2', $categorySelectElement);

    $i->wantTo('Edit segment and save');
    $editedTitle = 'Segment Woo Category Test Edited';
    $editedDesc = 'Segment description Edited';
    $i->fillField(['name' => 'name'], $editedTitle);
    $i->fillField(['name' => 'description'], $editedDesc);
    $i->selectOptionInReactSelect('Category 1', $categorySelectElement);
    $i->click('Save');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText($segmentTitle);

    $i->wantTo('Open edit form and check that all values were saved correctly');
    $i->clickItemRowActionByItemName($editedTitle, 'Edit');
    $i->waitForElement($categorySelectElement);
    $i->seeInField(['name' => 'name'], $editedTitle);
    $i->seeInField(['name' => 'description'], $editedDesc);
    $i->see('purchased in this category', $actionSelectElement);
    $i->see('Category 1', $categorySelectElement);
  }

  public function createAndEditWooCommercePurchasedProductSegment(\AcceptanceTester $i) {
    $i->activateWooCommerce();
    $productFactory = new WooCommerceProduct($i);
    $productFactory->withName('Product 1')->create();
    $productFactory->withName('Product 2')->create();
    $productSelectElement = '[data-automation-id="select-segment-product"]';
    $actionSelectElement = '[data-automation-id="select-segment-action"]';

    $i->wantTo('Create a new WooCommerce purchased product segment');
    $segmentTitle = 'Segment Woo Product Test';
    $segmentDesc = 'Segment description';
    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->click('[data-automation-id="new-segment"]');
    $i->fillField(['name' => 'name'], $segmentTitle);
    $i->fillField(['name' => 'description'], $segmentDesc);
    $i->selectOptionInReactSelect('purchased this product', $actionSelectElement);
    $i->waitForElement($productSelectElement);
    $i->selectOptionInReactSelect('Product 2', $productSelectElement);
    $i->click('Save');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText($segmentTitle);

    $i->wantTo('Open edit form and check that all values were saved correctly');
    $i->clickItemRowActionByItemName($segmentTitle, 'Edit');
    $i->waitForElement($productSelectElement);
    $i->seeInField(['name' => 'name'], $segmentTitle);
    $i->seeInField(['name' => 'description'], $segmentDesc);
    $i->see('purchased this product', $actionSelectElement);
    $i->see('Product 2', $productSelectElement);

    $i->wantTo('Edit segment and save');
    $editedTitle = 'Segment Woo Product Test Edited';
    $editedDesc = 'Segment description Edited';
    $i->fillField(['name' => 'name'], $editedTitle);
    $i->fillField(['name' => 'description'], $editedDesc);
    $i->selectOptionInReactSelect('Product 1', $productSelectElement);
    $i->click('Save');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText($segmentTitle);

    $i->wantTo('Open edit form and check that all values were saved correctly');
    $i->clickItemRowActionByItemName($editedTitle, 'Edit');
    $i->waitForElement($productSelectElement);
    $i->seeInField(['name' => 'name'], $editedTitle);
    $i->seeInField(['name' => 'description'], $editedDesc);
    $i->see('purchased this product', $actionSelectElement);
    $i->see('Product 1', $productSelectElement);
  }

  public function createAndEditWooCommerceNumberOfOrdersSegment(\AcceptanceTester $i) {
    $i->activateWooCommerce();
    $actionSelectElement = '[data-automation-id="select-segment-action"]';
    $numberOfOrdersTypeElement = '[data-automation-id="select-number-of-orders-type"]';
    $numberOfOrdersCountElement = '[data-automation-id="input-number-of-orders-count"]';
    $numberOfOrdersDaysElement = '[data-automation-id="input-number-of-orders-days"]';

    $i->wantTo('Create a new WooCommerce Number of Orders segment');
    $segmentTitle = 'Segment Woo Number of Orders Test';
    $segmentDesc = 'Segment description';
    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->click('[data-automation-id="new-segment"]');
    $i->fillField(['name' => 'name'], $segmentTitle);
    $i->fillField(['name' => 'description'], $segmentDesc);
    $i->selectOptionInReactSelect('# of orders', $actionSelectElement);
    $i->waitForElement($numberOfOrdersTypeElement);
    $i->selectOption($numberOfOrdersTypeElement, '>');
    $i->fillField($numberOfOrdersCountElement, 2);
    $i->fillField($numberOfOrdersDaysElement, 10);

    $i->click('Save');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText($segmentTitle);

    $i->wantTo('Open edit form and check that all values were saved correctly');
    $i->clickItemRowActionByItemName($segmentTitle, 'Edit');
    $i->waitForElement($numberOfOrdersTypeElement);
    $i->seeInField(['name' => 'name'], $segmentTitle);
    $i->seeInField(['name' => 'description'], $segmentDesc);
    $i->see('# of orders', $actionSelectElement);
    $i->see('more than', $numberOfOrdersTypeElement);
    $i->seeInField($numberOfOrdersCountElement, '2');
    $i->seeInField($numberOfOrdersDaysElement, '10');

    $i->wantTo('Edit segment and save');
    $editedTitle = 'Segment Woo Number of Orders Test Edited';
    $editedDesc = 'Segment description Edited';
    $i->fillField(['name' => 'name'], $editedTitle);
    $i->fillField(['name' => 'description'], $editedDesc);
    $i->selectOption($numberOfOrdersTypeElement, '=');
    $i->fillField($numberOfOrdersCountElement, 4);
    $i->fillField($numberOfOrdersDaysElement, 20);
    $i->click('Save');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText($segmentTitle);

    $i->wantTo('Open edit form and check that all values were saved correctly');
    $i->clickItemRowActionByItemName($editedTitle, 'Edit');
    $i->waitForElement($numberOfOrdersTypeElement);
    $i->seeInField(['name' => 'name'], $editedTitle);
    $i->seeInField(['name' => 'description'], $editedDesc);
    $i->see('# of orders', $actionSelectElement);
    $i->see('equal', $numberOfOrdersTypeElement);
    $i->seeInField($numberOfOrdersCountElement, '4');
    $i->seeInField($numberOfOrdersDaysElement, '20');
  }

  public function createAndEditWooCommerceTotalSpentSegment(\AcceptanceTester $i) {
    $i->activateWooCommerce();
    $actionSelectElement = '[data-automation-id="select-segment-action"]';
    $totalSpentTypeElement = '[data-automation-id="select-total-spent-type"]';
    $totalSpentAmountElement = '[data-automation-id="input-total-spent-amount"]';
    $totalSpentDaysElement = '[data-automation-id="input-total-spent-days"]';

    $i->wantTo('Create a new WooCommerce Total Spent segment');
    $segmentTitle = 'Segment Woo Total Spent Test';
    $segmentDesc = 'Segment description';
    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->click('[data-automation-id="new-segment"]');
    $i->fillField(['name' => 'name'], $segmentTitle);
    $i->fillField(['name' => 'description'], $segmentDesc);
    $i->selectOptionInReactSelect('total spent', $actionSelectElement);
    $i->waitForElement($totalSpentTypeElement);
    $i->selectOption($totalSpentTypeElement, '>');
    $i->fillField($totalSpentAmountElement, 2);
    $i->fillField($totalSpentDaysElement, 10);

    $i->click('Save');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText($segmentTitle);
    $i->seeNoJSErrors();

    $i->wantTo('Open edit form and check that all values were saved correctly');
    $i->clickItemRowActionByItemName($segmentTitle, 'Edit');
    $i->waitForElement($totalSpentTypeElement);
    $i->seeInField(['name' => 'name'], $segmentTitle);
    $i->seeInField(['name' => 'description'], $segmentDesc);
    $i->see('total spent', $actionSelectElement);
    $i->see('more than', $totalSpentTypeElement);
    $i->seeInField($totalSpentAmountElement, '2');
    $i->seeInField($totalSpentDaysElement, '10');

    $i->wantTo('Edit segment and save');
    $editedTitle = 'Segment Woo Total Spent Test Edited';
    $editedDesc = 'Segment description Edited';
    $i->fillField(['name' => 'name'], $editedTitle);
    $i->fillField(['name' => 'description'], $editedDesc);
    $i->selectOption($totalSpentTypeElement, '<');
    $i->fillField($totalSpentAmountElement, 4);
    $i->fillField($totalSpentDaysElement, 20);
    $i->click('Save');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText($segmentTitle);
    $i->seeNoJSErrors();

    $i->wantTo('Open edit form and check that all values were saved correctly');
    $i->clickItemRowActionByItemName($editedTitle, 'Edit');
    $i->waitForElement($totalSpentTypeElement);
    $i->seeInField(['name' => 'name'], $editedTitle);
    $i->seeInField(['name' => 'description'], $editedDesc);
    $i->see('total spent', $actionSelectElement);
    $i->see('less than', $totalSpentTypeElement);
    $i->seeInField($totalSpentAmountElement, '4');
    $i->seeInField($totalSpentDaysElement, '20');
  }
}
