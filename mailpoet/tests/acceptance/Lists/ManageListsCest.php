<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\User;
use PHPUnit\Framework\Assert;

class ManageListsCest {
  public function viewLists(\AcceptanceTester $i) {
    $i->wantTo('Open lists listings page');

    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->waitForText('WordPress Users', 5, '[data-automation-id="listing_item_1"]');
    $i->see('Newsletter mailing list', '[data-automation-id="listing_item_3"]');
    $i->seeNoJSErrors();
  }

  public function createNewList(\AcceptanceTester $i) {
    $i->wantTo('Create a new subscribers list');

    $newListTitle = 'Donkey Kong';
    $newListDesc = 'I am the hardest Donkey Kong!';

    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->click('New List');
    $i->click('[aria-label="Navigate to the lists page"]');
    $i->click('New List');
    $i->fillField('Name', $newListTitle);
    $i->fillField('Description', $newListDesc);
    $i->click('Save');
    $i->waitForText('WordPress Users', 5, '[data-automation-id="listing_item_1"]');
    $i->see($newListTitle, '[data-automation-id="listing_item_4"]');
    $i->seeNoJSErrors();
  }

  public function editTrashRestoreAndDeleteExistingList(\AcceptanceTester $i) {
    $i->wantTo('Edit, trash, restore and delete existing list');

    $newListTitle = 'Donkey Kong';
    $newListDesc = 'I am the hardest Donkey Kong!';
    $editedListTitle = 'King Kong';
    $editedListDesc = 'Hardest King Kong in the world!';
    $segmentFactory = new Segment();
    $segmentFactory
      ->withName($newListTitle)
      ->withDescription($newListDesc)
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Lists');

    $i->wantTo('Edit existing list');
    $i->waitForText('Lists');
    $i->clickItemRowActionByItemName($newListTitle, 'Edit');
    $i->waitForText($newListTitle);
    $i->clearFormField('#field_name');
    $i->fillField('Name', $editedListTitle);
    $i->fillField('Description', $editedListDesc);
    $i->click('Save');
    $i->waitForNoticeAndClose('List successfully updated!');
    $i->waitForText('WordPress Users', 5, '[data-automation-id="listing_item_1"]');
    $i->see($editedListTitle, '[data-automation-id="listing_item_4"]');
    $i->seeNoJSErrors();

    $i->wantTo('Trash existing list');
    $i->clickItemRowActionByItemName($editedListTitle, 'Move to trash');
    $i->waitForNoticeAndClose('1 list was moved to the trash. Note that deleting a list does not delete its subscribers.');
    $i->changeGroupInListingFilter('trash');
    $i->waitForText($editedListTitle);
    $i->seeNoJSErrors();

    $i->wantTo('Restore trashed list');
    $i->clickItemRowActionByItemName($editedListTitle, 'Restore');
    $i->waitForNoticeAndClose('1 list has been restored from the Trash.');
    $i->waitForElementNotVisible('[data-automation-id="filters_trash"]');
    $i->seeInCurrentURL(urlencode('group[all]'));
    $i->waitForText($editedListTitle);
    $i->seeNoJSErrors();

    $i->wantTo('Trash and delete existing list');
    $segmentFactory = new Segment();
    $segmentFactory
      ->withName($newListTitle . '2')
      ->withDescription($newListDesc)
      ->create();
    $i->clickItemRowActionByItemName($editedListTitle, 'Move to trash');
    $i->waitForNoticeAndClose('1 list was moved to the trash. Note that deleting a list does not delete its subscribers.');
    $i->reloadPage(); // just to clear all notifications from the above
    $i->changeGroupInListingFilter('trash');
    $i->waitForText($editedListTitle);
    $i->clickItemRowActionByItemName($editedListTitle, 'Delete permanently');
    $i->waitForNoticeAndClose('1 list was permanently deleted. Note that deleting a list does not delete its subscribers.');
    $i->seeNoJSErrors();
    $i->waitForElementNotVisible('[data-automation-id="filters_trash"]');
    $i->seeInCurrentURL(urlencode('group[all]'));
    $i->reloadPage();
    $i->waitForText($newListTitle . '2');
    $i->dontSee($editedListTitle, '[data-automation-id="listing_item_4"]');
  }

  public function emptyTrash(\AcceptanceTester $i) {
    $i->wantTo('Trash existing list by clicking on Empty Trash button');
    $newListTitle = 'Empty Trash List';
    $newListDesc = 'Description';
    $segmentFactory = new Segment();
    $segmentFactory
      ->withName($newListTitle)
      ->withDescription($newListDesc)
      ->withDeleted()
      ->create();
    $segmentFactory = new Segment();
    $segmentFactory
      ->withName('List to keep')
      ->withDescription($newListDesc)
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->changeGroupInListingFilter('trash');
    $i->waitForText($newListTitle);
    $i->click('[data-automation-id="empty_trash"]');

    $i->waitForText('1 list was permanently deleted. Note that deleting a list does not delete its subscribers.');
    $i->dontSee($newListTitle);
    $i->seeNoJSErrors();
    $i->changeGroupInListingFilter('all');
    $i->see('List to keep');
  }

  public function disableAndEnableWPUserList(\AcceptanceTester $i) {
    $listName = 'WordPress Users';

    $userFactory = new User();
    $userFactory->createUser('Test User', 'editor', 'test-editor@example.com');

    $i->wantTo('Disable WP User list by clicking on Trash and disable button');
    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->waitForText($listName, 5, '[data-automation-id="listing_item_1"]');
    $i->clickItemRowActionByItemName($listName, 'Trash and disable');
    $i->waitForText('1 list was moved to the trash. Note that deleting a list does not delete its subscribers.');
    $i->seeNoJSErrors();

    $i->wantTo('See WP User list in the Trash');
    $i->changeGroupInListingFilter('trash');
    $i->waitForText($listName);
    $i->seeNoJSErrors();

    $i->wantTo('Check trashed WP User');
    $i->amOnMailPoetPage('Subscribers');
    $i->changeGroupInListingFilter('trash');
    $i->waitForText('test-editor@example.com', 5);

    $i->reloadPage();

    $i->wantTo('Enable WP User list by clicking on Restore and enable button');
    $i->amOnMailpoetPage('Lists');
    $i->changeGroupInListingFilter('trash');
    $i->clickItemRowActionByItemName($listName, 'Restore and enable');
    $i->seeNoJSErrors();

    $i->wantTo('See WP User list in the Trash');
    $i->amOnMailpoetPage('Lists');
    $i->waitForText($listName, 5, '[data-automation-id="listing_item_1"]');
    $i->seeNoJSErrors();

    $i->wantTo('Check WP User is restored');
    $i->amOnMailPoetPage('Subscribers');
    $i->waitForText('test-editor@example.com', 5);
  }

  public function cantTrashOrBulkTrashActivelyUsedList(\AcceptanceTester $i) {
    $listTitle = 'Active List';
    $subject = 'Post notification';
    $segmentFactory = new Segment();
    $segment = $segmentFactory
      ->withName($listTitle)
      ->create();
    $newsletterFactory = new Newsletter();
    $newsletterFactory->withPostNotificationsType()
      ->withSegments([$segment])
      ->withSubject($subject)
      ->create();

    $i->wantTo('Check that user can’t delete actively used list');
    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->waitForText($listTitle, 5, '[data-automation-id="listing_item_' . $segment->getId() . '"]');
    $i->clickItemRowActionByItemName($listTitle, 'Move to trash');
    $i->waitForText("List cannot be deleted because it’s used for '{$subject}' email");
    $i->seeNoJSErrors();
    $i->checkOption('[data-automation-id="listing-row-checkbox-' . $segment->getId() . '"]');
    $i->waitForText('Move to trash');
    $i->click('Move to trash');
    $i->waitForText('0 lists were moved to the trash.');
  }

  public function cantTrashOrBulkTrashListWithForm(\AcceptanceTester $i) {
    $listTitle = 'List with form';
    $segmentFactory = new Segment();
    $segment = $segmentFactory
      ->withName($listTitle)
      ->create();
    $formName = 'My Form';
    $formFactory = new Form();
    $formFactory
      ->withName($formName)
      ->withSegments([$segment])
      ->create();

    $i->wantTo('Check that user can’t delete a list that is assigned to a form');
    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->waitForText($listTitle, 5, '[data-automation-id="listing_item_' . $segment->getId() . '"]');
    $i->clickItemRowActionByItemName($listTitle, 'Move to trash');
    $i->waitForText("List cannot be deleted because it’s used for '{$formName}' form");
    $i->seeNoJSErrors();
    $i->checkOption('[data-automation-id="listing-row-checkbox-' . $segment->getId() . '"]');
    $i->waitForText('Move to trash');
    $i->click('Move to trash');
    $i->waitForText('0 lists were moved to the trash.');
  }

  public function cannotDisableWPUserList(\AcceptanceTester $i) {
    $listName = 'WordPress Users';
    $subject = 'Blocking Post Notification';

    $segment = ContainerWrapper::getInstance()->get(SegmentsRepository::class)->findOneById(1);
    Assert::assertInstanceOf(SegmentEntity::class, $segment);

    $newsletterFactory = new Newsletter();
    $newsletterFactory
      ->withSubject($subject)
      ->withSegments([$segment])
      ->withPostNotificationsType()
      ->create();

    $i->wantTo('Cannot disable WP User list by clicking on Trash and disable button');
    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->waitForText($listName, 5, '[data-automation-id="listing_item_1"]');
    $i->clickItemRowActionByItemName($listName, 'Trash and disable');
    $i->waitForText("List cannot be deleted because it’s used for '{$subject}' email");
    $i->seeNoJSErrors();
  }
}
