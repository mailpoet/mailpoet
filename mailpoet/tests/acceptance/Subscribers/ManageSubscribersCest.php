<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;

class ManageSubscribersCest {

  const ACTIVE_SUBSCRIBERS_COUNT = 3;
  const INACTIVE_SUBSCRIBERS_COUNT = 4;
  const INACTIVE_LIST_NAME = 'Inactivity';

  /** @var SegmentEntity */
  private $segment;

  public function _before() {
    $segmentFactory = new Segment();
    $this->segment = $segmentFactory->withName('Subscriber Management Test List')->create();
  }

  private function generateWPUsersList(\AcceptanceTester $i) {
    $i->wantTo('Create some subscribers');
    $i->cli(['user', 'import-csv', '/wp-core/wp-content/plugins/mailpoet/tests/_data/users.csv']);
    $i->login();
    $i->amOnMailPoetPage ('Subscribers');
    $i->seeNoJSErrors();
  }

  private function generateSingleSubscriber($newSubscriberEmail, $subscriberFirstName, $subscriberLastName) {
    $subscriberFactory = new Subscriber();
    return $subscriberFactory
      ->withEmail($newSubscriberEmail)
      ->withFirstName($subscriberFirstName)
      ->withLastName($subscriberLastName)
      ->create();
  }

  private function generateMultipleLists() {
    $segmentFactory1 = new Segment();
    $segmentFactory1->withName('Cooking')->create();
    $segmentFactory2 = new Segment();
    $segmentFactory2->withName('Camping')->create();
  }

  private function prepareInactiveSubscribersData() {
    $segment = (new Segment())->withName(self::INACTIVE_LIST_NAME)->create();
    for ($i = 0; $i < self::ACTIVE_SUBSCRIBERS_COUNT; $i++) {
      (new Subscriber())->withSegments([$segment])->create();
    }
    for ($i = 0; $i < self::INACTIVE_SUBSCRIBERS_COUNT; $i++) {
      (new Subscriber())->withStatus('inactive')->withSegments([$segment])->create();
    }
  }

  public function viewSubscriberList(\AcceptanceTester $i) {
    $i->wantTo('View list of subscribers');
    $this->generateWPUsersList($i);
    $i->searchFor('Alec Saunders');
    $i->waitForText('Alec Saunders');
    $i->seeNoJSErrors();
  }

  public function addGlobalSubscriber(\AcceptanceTester $i) {
    $i->wantTo('Add a user to global subscribers list');
    $i->login();
    $i->amOnMailPoetPage ('Subscribers');
    $i->click('[data-automation-id="add-new-subscribers-button"]');
    $i->fillField(['name' => 'email'], 'newglobaluser99@fakemail.fake');
    $i->fillField(['name' => 'first_name'], 'New');
    $i->fillField(['name' => 'last_name'], 'GlobalUser');
    $i->selectOptionInSelect2($this->segment->getName());
    $i->click('[data-automation-id="subscriber_edit_form"] [type="submit"]');
    $i->amOnMailPoetPage ('Subscribers');
    $i->searchFor('newglobaluser99@fakemail.fake');
    $i->waitForText('newglobaluser99@fakemail.fake');
    $i->seeNoJSErrors();
  }

  public function deleteGlobalSubscriber(\AcceptanceTester $i) {
    $i->wantTo('Delete a user from global subscribers list');
    $newSubscriberEmail = 'deleteglobaluser99@fakemail.fake';
    $this->generateSingleSubscriber('deleteglobaluser99@fakemail.fake', 'Delete', 'ThisGlobalUser');
    $i->login();
    $i->amOnMailPoetPage('Subscribers');
    $i->waitForListingItemsToLoad();
    $i->clickItemRowActionByItemName($newSubscriberEmail, 'Move to trash');
    $i->waitForElement('[data-automation-id="filters_trash"]');
    $i->click('[data-automation-id="filters_trash"]');
    $i->waitForText($newSubscriberEmail);
    $i->clickItemRowActionByItemName($newSubscriberEmail, 'Restore');
    $i->amOnMailpoetPage('Subscribers');
    $i->waitForText($newSubscriberEmail, 20);
    $i->seeNoJSErrors();
  }

  public function deleteGlobalSubscriberForever(\AcceptanceTester $i) {
    $i->wantTo('Delete a subscriber forever');
    $newSubscriberEmail = 'deletesubscriberforever@fakemail.fake';
    $newSubscriberEmail2 = 'deletesubscriberforever2@fakemail.fake';
    $this->generateSingleSubscriber($newSubscriberEmail, 'Delete', 'ThisGlobalUser');
    $this->generateSingleSubscriber($newSubscriberEmail2, 'Keep', 'ThisSubscriber');
    $i->login();
    $i->amOnMailPoetPage('Subscribers');
    $i->waitForListingItemsToLoad();
    $i->clickItemRowActionByItemName($newSubscriberEmail, 'Move to trash');
    $i->waitForListingItemsToLoad();
    $i->clickItemRowActionByItemName($newSubscriberEmail2, 'Move to trash');
    $i->waitForListingItemsToLoad();
    $i->waitForElementClickable('[data-automation-id="filters_trash"]');
    $i->click('[data-automation-id="filters_trash"]');
    $i->waitForListingItemsToLoad();
    $i->waitForText($newSubscriberEmail);
    $i->clickItemRowActionByItemName($newSubscriberEmail, 'Delete Permanently');
    $i->waitForText('1 subscriber was permanently deleted.');
    $i->dontSee($newSubscriberEmail);

    $i->waitForText($newSubscriberEmail2, 20);
    $i->seeNoJSErrors();
  }

  public function emptyTrash(\AcceptanceTester $i) {
    $i->wantTo('Delete a subscriber forever');
    $newSubscriberEmail = 'deletesubscriberforever@fakemail.fake';
    $newSubscriberEmail2 = 'deletesubscriberforever2@fakemail.fake';
    $this->generateSingleSubscriber($newSubscriberEmail, 'Delete', 'ThisGlobalUser');
    $this->generateSingleSubscriber($newSubscriberEmail2, 'Keep', 'ThisSubscriber');
    $i->login();
    $i->amOnMailPoetPage('Subscribers');
    $i->waitForListingItemsToLoad();
    $i->clickItemRowActionByItemName($newSubscriberEmail, 'Move to trash');

    $i->waitForElement('[data-automation-id="filters_trash"]');
    $i->click('[data-automation-id="filters_trash"]');
    $i->waitForText($newSubscriberEmail, 20);

    $i->click('[data-automation-id="empty_trash"]');

    $i->waitForText('1 subscriber was permanently deleted.');
    $i->dontSee($newSubscriberEmail);
    $i->click('[data-automation-id="filters_all"]');

    $i->waitForText($newSubscriberEmail2, 20);
    $i->seeNoJSErrors();
  }

  public function addSubscriberToList(\AcceptanceTester $i) {
    $i->wantTo('Add a subscriber to a list');
    $newSubscriberEmail = 'addtolistuser99@fakemail.fake';
    $this->generateMultipleLists();
    $this->generateSingleSubscriber('addtolistuser99@fakemail.fake', 'Add', 'ToAList');
    $i->login();
    $i->amOnMailPoetPage ('Subscribers');
    $i->waitForListingItemsToLoad();
    $i->clickItemRowActionByItemName($newSubscriberEmail, 'Edit');
    $i->waitForText('Subscriber');
    $i->waitForElementNotVisible('.mailpoet_form_loading');
    $i->selectOptionInSelect2('Cooking');
    $i->click('[data-automation-id="subscriber_edit_form"] [type="submit"]');
    $i->seeNoJSErrors();
  }

  public function deleteSubscriberFromList(\AcceptanceTester $i) {
    $i->wantTo('Delete a subscriber from a list');
    $newSubscriberEmail = 'deletefromlistuser99@fakemail.fake';
    $this->generateMultipleLists();
    $this->generateSingleSubscriber('deletefromlistuser99@fakemail.fake', 'Delete', 'FromAList');
    $i->login();
    $i->amOnMailPoetPage ('Subscribers');
    $i->waitForListingItemsToLoad();
    $i->clickItemRowActionByItemName($newSubscriberEmail, 'Edit');
    $i->waitForText('Subscriber');
    $i->waitForElementNotVisible('.mailpoet_form_loading');
    $i->selectOptionInSelect2('Cooking');
    $i->click('.select2-selection__choice__remove');
    $i->click('[data-automation-id="subscriber_edit_form"] [type="submit"]');
    $i->waitForText('Subscriber was updated');
  }

  public function editSubscriber(\AcceptanceTester $i) {
    $i->wantTo('Edit a subscriber');
    $newSubscriberEmail = 'editglobaluser99@fakemail.fake';
    $unsubscribedMessage = ['xpath' => '//*[@class="description"]'];
    $this->generateMultipleLists();
    $this->generateSingleSubscriber('editglobaluser99@fakemail.fake', 'Edit', 'ThisGlobalUser');
    $i->login();
    $i->amOnMailPoetPage ('Subscribers');
    $i->waitForListingItemsToLoad();
    $i->clickItemRowActionByItemName($newSubscriberEmail, 'Edit');
    $i->waitForText('Subscriber');
    $i->fillField(['name' => 'first_name'], 'EditedNew');
    $i->fillField(['name' => 'last_name'], 'EditedGlobalUser');
    $i->selectOption('[data-automation-id="subscriber-status"]', 'Unsubscribed');
    // we cannot use data-automation attribute because this input is based on guttenberg component
    $i->fillField('.mailpoet-form-field-tags input[type="text"]', 'My tag,'); // the comma separates the tag
    $i->click('Save');
    $i->waitForElementVisible('[data-automation-id="listing_item_1"]');
    $i->clickItemRowActionByItemName($newSubscriberEmail, 'Edit');
    $i->waitForText('Subscriber');
    $i->waitForElementNotVisible('.mailpoet_form_loading');
    $i->seeOptionIsSelected('[data-automation-id="subscriber-status"]', 'Unsubscribed');
    $i->see('Unsubscribed at', $unsubscribedMessage);
    // tag is visible
    $i->see('My tag');
    $i->selectOptionInSelect2('Cooking');
    $i->selectOptionInSelect2('Camping');
    $i->waitForElementClickable('[data-automation-id="subscriber-status"]');
    $i->selectOption('[data-automation-id="subscriber-status"]', 'Subscribed');
    // remove tag
    $i->click(Locator::firstElement('.mailpoet-form-field-tags button[aria-label="Remove item"]'));
    $i->click('Save');
    $i->waitForElementVisible('[data-automation-id="listing_item_1"]');
    $i->clickItemRowActionByItemName($newSubscriberEmail, 'Edit');
    $i->waitForText('Subscriber');
    $i->seeSelectedInSelect2('Cooking');
    $i->seeSelectedInSelect2('Camping');
    $i->seeOptionIsSelected('[data-automation-id="subscriber-status"]', 'Subscribed');
    // check that tag is removed
    $i->seeInField('.mailpoet-form-field-tags input[type="text"]', '');
    $i->dontSee('My tag');
  }

  public function inactiveSubscribers(\AcceptanceTester $i) {
    $i->wantTo('Check inactive subscribers');
    $this->prepareInactiveSubscribersData();
    $i->login();
    $i->amOnMailPoetPage ('Subscribers');

    // Filter inactive subscribers
    $i->waitForElement('[data-automation-id="filters_inactive"]');
    $i->click('[data-automation-id="filters_inactive"]');
    $i->waitForListingItemsToLoad();
    $i->seeNumberOfElements('[data-automation-id^="listing_item_"]', self::INACTIVE_SUBSCRIBERS_COUNT);

    // Check inactive status in subscriber detail
    $i->click('@example.com');
    $i->waitForElementNotVisible('.mailpoet_form_loading');
    $i->seeOptionIsSelected('[data-automation-id="subscriber-status"]', 'Inactive');

    // Check correct list counts
    $i->amOnMailpoetPage('Lists');
    $i->waitForListingItemsToLoad();
    $i->waitForText(self::INACTIVE_LIST_NAME);
    $this->seeListCountByStatus($i, self::INACTIVE_SUBSCRIBERS_COUNT, self::INACTIVE_LIST_NAME, 'Inactive');
    $this->seeListCountByStatus($i, self::ACTIVE_SUBSCRIBERS_COUNT, self::INACTIVE_LIST_NAME, 'Subscribed');
  }

  private function seeListCountByStatus(\AcceptanceTester $i, $count, $listName, $status) {
    $i->see($count, "//*[@class='mailpoet-listing-title'][contains(text(), '$listName')]/ancestor::tr/td[@data-colname='$status']");
  }
}
