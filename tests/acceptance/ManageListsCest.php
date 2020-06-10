<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Segment;

class ManageListsCest {
  public function viewLists(\AcceptanceTester $i) {
    $i->wantTo('Open lists listings page');

    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->waitForText('WordPress Users', 5, '[data-automation-id="listing_item_1"]');
    $i->see('My First List', '[data-automation-id="listing_item_3"]');
    $i->seeNoJSErrors();
  }

  public function createNewList(\AcceptanceTester $i) {
    $i->wantTo('Create a new subscribers list');

    $newListTitle = 'Donkey Kong';
    $newListDesc = 'I am the hardest Donkey Kong!';

    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->click('New List');
    $i->click('Back');
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
    $segment = $segmentFactory
      ->withName($newListTitle)
      ->withDescription($newListDesc)
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Lists');

    // Edit existing list
    $i->clickItemRowActionByItemName($newListTitle, 'Edit');
    $i->fillField('Name', $editedListTitle);
    $i->fillField('Description', $editedListDesc);
    $i->click('Save');
    $i->waitForText('WordPress Users', 5, '[data-automation-id="listing_item_1"]');
    $i->see($editedListTitle, '[data-automation-id="listing_item_4"]');
    $i->seeNoJSErrors();

    // Trash existing list
    $i->clickItemRowActionByItemName($editedListTitle, 'Move to trash');
    $i->waitForText('1 list was moved to the trash. Note that deleting a list does not delete its subscribers.', 10);
    $i->waitForElementVisible('[data-automation-id="filters_trash"]', 10);
    $i->click('[data-automation-id="filters_trash"]');
    $i->waitForElementVisible('[data-automation-id="filters_trash"]', 10);
    $i->waitForText($editedListTitle, 10);
    $i->seeNoJSErrors();

    // Restore trashed list
    $i->clickItemRowActionByItemName($editedListTitle, 'Restore');
    $i->waitForText('1 list has been restored from the Trash.', 10);
    $i->seeInCurrentURL(urlencode('group[all]'));
    $i->waitForText($editedListTitle, 10);
    $i->seeNoJSErrors();

    // Trash and delete existing list
    $i->clickItemRowActionByItemName($editedListTitle, 'Move to trash');
    $i->waitForElementVisible('[data-automation-id="filters_trash"]', 10);
    $i->click('[data-automation-id="filters_trash"]');
    $i->waitForElementVisible('[data-automation-id="filters_trash"]', 10);
    $i->clickItemRowActionByItemName($editedListTitle, 'Delete permanently');
    $i->waitForText('1 list was permanently deleted. Note that deleting a list does not delete its subscribers.', 10);
    $i->seeNoJSErrors();
    $i->seeInCurrentURL(urlencode('group[all]'));
    $i->dontSee($editedListTitle, '[data-automation-id="listing_item_4"]');
  }
}
