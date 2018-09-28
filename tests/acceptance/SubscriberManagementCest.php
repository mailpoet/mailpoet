<?php

namespace MailPoet\Test\Acceptance;

class SubscriberManagementCest {
  function __construct() {
    $this->search_field_element = 'input.select2-search__field';
  }
  Private function generateWPUsersList(\AcceptanceTester $I){
    $I->wantTo('Create some subscribers');
    $I->cli('user import-csv --path /wp-core/wp-content/plugins/mailpoet/tests/_data/users.csv --allow-root');
    $I->login();
    $I->amOnMailPoetPage ('Subscribers');
  }

  Private function generateSingleSubscriber(\AcceptanceTester $I, $username, $new_subscriber_email, $subscriber_first_name, $subscriber_last_name){
    $I->login();
    $I->amOnMailPoetPage ('Subscribers');
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-subscribers#/new');
    $I->fillField(['name' => 'email'], $new_subscriber_email);
    $I->fillField(['name' => 'first_name'], $subscriber_first_name);
    $I->fillField(['name' => 'last_name'], $subscriber_last_name);
    $I->fillField($this->search_field_element, 'My First List');
    $I->pressKey($this->search_field_element, \WebDriverKeys::ENTER);
    $I->click('Save');
    $I->amOnMailPoetPage ('Subscribers');
    $I->fillField('#search_input', $new_subscriber_email);
    $I->click('Search');
    $I->waitForText($new_subscriber_email, 10);
  }

  Private function generateMultipleLists(\AcceptanceTester $I){
    $I->login();
    $I->amOnMailPoetPage('Lists');
    $I->click(['css'=> '.page-title-action']);
    $I->waitForText('Description', 10);
    $I->fillField(['name' => 'name'], "Cooking");
    $I->click('Save');
    $I->amOnMailpoetPage('Lists');
    $I->waitForText('Cooking', 20);
    $I->click(['css'=> '.page-title-action']);
    $I->waitForText('Description', 10);
    $I->fillField(['name' => 'name'], "Camping");
    $I->click('Save');
    $I->amOnMailpoetPage('Lists');
    $I->waitForText('Camping', 20);
  }

  function viewSubscriberList(\AcceptanceTester $I){
    $I->wantTo('View list of subscribers');
    $this->generateWPUsersList($I);
    $I->fillField('#search_input', 'Alec Saunders');
    $I->click('Search');
    $I->waitForText('Alec Saunders', 10);
  }

  function addGlobalSubscriber(\AcceptanceTester $I){
    $I->wantTo('Add a user to global subscribers list');
    $this->generateSingleSubscriber($I, 'newglobal', 'newglobaluser99@fakemail.fake', 'New', 'GlobalUser');
  }

  function deleteGlobalSubscriber(\AcceptanceTester $I){
    $I->wantTo('Delete a user from global subscribers list');
    $new_subscriber_email = 'deleteglobaluser99@fakemail.fake';
    $this->generateSingleSubscriber($I, 'deleteglobal', 'deleteglobaluser99@fakemail.fake', 'Delete', 'ThisGlobalUser');
    $I->clickItemRowActionByItemName($new_subscriber_email, 'Move to trash');
    $I->waitForElement('[data-automation-id="filters_trash"]');
    $I->click('[data-automation-id="filters_trash"]');
    $I->waitForText($new_subscriber_email);
    $I->clickItemRowActionByItemName($new_subscriber_email, 'Restore');
    $I->amOnMailpoetPage('Subscribers');
    $I->waitForText($new_subscriber_email);
  }

  function addSubscriberToList(\AcceptanceTester $I){
    $I->wantTo('Add a subsciber to a list');
    $new_subscriber_email = 'addtolistuser99@fakemail.fake';
    $this->generateMultipleLists($I);
    $this->generateSingleSubscriber($I, 'addtolist', 'addtolistuser99@fakemail.fake', 'Add', 'ToAList');
    $I->clickItemRowActionByItemName($new_subscriber_email, 'Edit');
    $I->waitForText('Subscriber', 30);
    $I->seeInCurrentUrl('mailpoet-subscribers#/edit/');
    $I->fillField($this->search_field_element, 'Cooking');
    $I->pressKey($this->search_field_element, \WebDriverKeys::ENTER);
    $I->click('Save');
  }

  function deleteSubscriberFromList(\AcceptanceTester $I){
    $I->wantTo('Delete a subscriber from a list');
    $new_subscriber_email = 'deletefromlistuser99@fakemail.fake';
    $this->generateSingleSubscriber($I, 'deletefromlist', 'deletefromlistuser99@fakemail.fake', 'Delete', 'FromAList');
    $I->clickItemRowActionByItemName($new_subscriber_email, 'Edit');
    $I->waitForText('Subscriber', 10);
    $I->seeInCurrentUrl('mailpoet-subscribers#/edit/');
    $I->fillField($this->search_field_element, 'Cooking');
    $I->pressKey($this->search_field_element, \WebDriverKeys::ENTER);
    $I->click('.select2-selection__choice__remove');
    $I->click('Save');
    $I->waitForText('Subscriber was updated', 10);
  }

  function editSubscriber(\AcceptanceTester $I){
    $I->wantTo('Edit a subscriber');
    $new_subscriber_email = 'editglobaluser99@fakemail.fake';
    $this->generateSingleSubscriber($I, 'editglobal', 'editglobaluser99@fakemail.fake', 'Edit', 'ThisGlobalUser');
    $I->clickItemRowActionByItemName($new_subscriber_email, 'Edit');
    $I->waitForText('Subscriber', 10);
    $I->seeInCurrentUrl('mailpoet-subscribers#/edit/');
  }

}