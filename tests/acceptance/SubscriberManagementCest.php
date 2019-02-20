<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;

require_once __DIR__ . '/../DataFactories/Segment.php';
require_once __DIR__ . '/../DataFactories/Subscriber.php';

class SubscriberManagementCest {

  /** @var \MailPoet\Models\Segment */
  private $segment;

  public function _before() {
    $segment_factory = new Segment();
    $this->segment = $segment_factory->withName('Subscriber Management Test List')->create();
  }

  private function generateWPUsersList(\AcceptanceTester $I) {
    $I->wantTo('Create some subscribers');
    $I->cli('user import-csv --path /wp-core/wp-content/plugins/mailpoet/tests/_data/users.csv --allow-root');
    $I->login();
    $I->amOnMailPoetPage ('Subscribers');
    $I->seeNoJSErrors();
  }

  private function generateSingleSubscriber($new_subscriber_email, $subscriber_first_name, $subscriber_last_name) {
    $subscriber_factory = new Subscriber();
    return $subscriber_factory
      ->withEmail($new_subscriber_email)
      ->withFirstName($subscriber_first_name)
      ->withLastName($subscriber_last_name)
      ->create();
  }

  private function generateMultipleLists() {
    $segment_factory1 = new Segment();
    $segment_factory1->withName('Cooking')->create();
    $segment_factory2 = new Segment();
    $segment_factory2->withName('Camping')->create();
  }

  function viewSubscriberList(\AcceptanceTester $I) {
    $I->wantTo('View list of subscribers');
    $this->generateWPUsersList($I);
    $I->searchFor('Alec Saunders', 2);
    $I->waitForText('Alec Saunders');
    $I->seeNoJSErrors();
  }

  function addGlobalSubscriber(\AcceptanceTester $I) {
    $I->wantTo('Add a user to global subscribers list');
    $I->login();
    $I->amOnMailPoetPage ('Subscribers');
    $I->click(['xpath'=>'//*[@id="subscribers_container"]/div/h1/a[1]']);
    $I->fillField(['name' => 'email'], 'newglobaluser99@fakemail.fake');
    $I->fillField(['name' => 'first_name'], 'New');
    $I->fillField(['name' => 'last_name'], 'GlobalUser');
    $I->selectOptionInSelect2($this->segment->get('name'));
    $I->click('[data-automation-id="subscriber_edit_form"] input[type="submit"]');
    $I->amOnMailPoetPage ('Subscribers');
    $I->searchFor('newglobaluser99@fakemail.fake', 2);
    $I->waitForText('newglobaluser99@fakemail.fake');
    $I->seeNoJSErrors();
  }

  function deleteGlobalSubscriber(\AcceptanceTester $I) {
    $I->wantTo('Delete a user from global subscribers list');
    $new_subscriber_email = 'deleteglobaluser99@fakemail.fake';
    $this->generateSingleSubscriber('deleteglobaluser99@fakemail.fake', 'Delete', 'ThisGlobalUser');
    $I->login();
    $I->amOnMailPoetPage('Subscribers');
    $I->waitForListingItemsToLoad();
    $I->clickItemRowActionByItemName($new_subscriber_email, 'Move to trash');
    $I->waitForElement('[data-automation-id="filters_trash"]');
    $I->click('[data-automation-id="filters_trash"]');
    $I->waitForText($new_subscriber_email);
    $I->clickItemRowActionByItemName($new_subscriber_email, 'Restore');
    $I->amOnMailpoetPage('Subscribers');
    $I->waitForText($new_subscriber_email);
    $I->seeNoJSErrors();
  }

  function addSubscriberToList(\AcceptanceTester $I) {
    $I->wantTo('Add a subscriber to a list');
    $new_subscriber_email = 'addtolistuser99@fakemail.fake';
    $this->generateMultipleLists();
    $this->generateSingleSubscriber('addtolistuser99@fakemail.fake', 'Add', 'ToAList');
    $I->login();
    $I->amOnMailPoetPage ('Subscribers');
    $I->waitForListingItemsToLoad();
    $I->clickItemRowActionByItemName($new_subscriber_email, 'Edit');
    $I->waitForText('Subscriber');
    $I->waitForElementNotVisible('.mailpoet_form_loading');
    $I->selectOptionInSelect2('Cooking');
    $I->click('[data-automation-id="subscriber_edit_form"] input[type="submit"]');
    $I->seeNoJSErrors();
  }

  function deleteSubscriberFromList(\AcceptanceTester $I) {
    $I->wantTo('Delete a subscriber from a list');
    $new_subscriber_email = 'deletefromlistuser99@fakemail.fake';
    $this->generateSingleSubscriber('deletefromlistuser99@fakemail.fake', 'Delete', 'FromAList');
    $I->login();
    $I->amOnMailPoetPage ('Subscribers');
    $I->waitForListingItemsToLoad();
    $I->clickItemRowActionByItemName($new_subscriber_email, 'Edit');
    $I->waitForText('Subscriber');
    $I->waitForElementNotVisible('.mailpoet_form_loading');
    $I->selectOptionInSelect2('Cooking');
    $I->click('.select2-selection__choice__remove');
    $I->click('[data-automation-id="subscriber_edit_form"] input[type="submit"]');
    $I->waitForText('Subscriber was updated');
  }

  function editSubscriber(\AcceptanceTester $I) {
    $I->wantTo('Edit a subscriber');
    $new_subscriber_email = 'editglobaluser99@fakemail.fake';
    $this->generateSingleSubscriber('editglobaluser99@fakemail.fake', 'Edit', 'ThisGlobalUser');
    $I->login();
    $I->amOnMailPoetPage ('Subscribers');
    $I->waitForListingItemsToLoad();
    $I->clickItemRowActionByItemName($new_subscriber_email, 'Edit');
    $I->waitForText('Subscriber');
  }

}
