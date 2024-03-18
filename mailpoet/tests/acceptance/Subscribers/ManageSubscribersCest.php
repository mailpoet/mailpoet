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
  const SINGLE_SEGMENT_NAME = 'Subscriber Management Test List';
  const MULTIPLE_SEGMENT_NAME_COOKING = 'Cooking';
  const MULTIPLE_SEGMENT_NAME_CAMPING = 'Camping';
  const SUBSCRIBER_UPDATED_NOTICE = 'Subscriber was updated successfully!';

  /** @var SegmentEntity */
  private $segment;

  public function _before() {
    $segmentFactory = new Segment();
    $this->segment = $segmentFactory->withName(self::SINGLE_SEGMENT_NAME)->create();
  }

  private function generateWPUsersList(\AcceptanceTester $i) {
    $i->wantTo('Create some subscribers');

    $i->cli(['user', 'import-csv', '/wp-core/wp-content/plugins/mailpoet/tests/_data/users.csv']);

    $i->login();
    $i->amOnMailPoetPage('Subscribers');
    $i->seeNoJSErrors();
  }

  private function generateSingleSubscriber($newSubscriberEmail, $subscriberFirstName, $subscriberLastName, $segment) {
    $subscriberFactory = new Subscriber();
    return $subscriberFactory
      ->withEmail($newSubscriberEmail)
      ->withFirstName($subscriberFirstName)
      ->withLastName($subscriberLastName)
      ->withSegments([$segment])
      ->create();
  }

  private function generateMultipleLists() {
    $segmentFactory1 = new Segment();
    $segmentFactory1->withName(self::MULTIPLE_SEGMENT_NAME_COOKING)->create();
    $segmentFactory2 = new Segment();
    $segmentFactory2->withName(self::MULTIPLE_SEGMENT_NAME_CAMPING)->create();
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
    $i->amOnMailPoetPage('Subscribers');
    $i->click('[data-automation-id="add-new-subscribers-button"]');
    $i->fillField(['name' => 'email'], 'newglobaluser99@fakemail.fake');
    $i->fillField(['name' => 'first_name'], 'New');
    $i->fillField(['name' => 'last_name'], 'GlobalUser');
    $i->selectOptionInSelect2($this->segment->getName());
    $i->click('Save');
    $i->amOnMailPoetPage('Subscribers');
    $i->searchFor('newglobaluser99@fakemail.fake');
    $i->waitForText('newglobaluser99@fakemail.fake');
    $i->seeNoJSErrors();
  }

  public function deleteGlobalSubscriber(\AcceptanceTester $i) {
    $i->wantTo('Delete a user from global subscribers list');

    $newSubscriberEmail = 'deleteglobaluser99@fakemail.fake';

    $this->generateSingleSubscriber('deleteglobaluser99@fakemail.fake', 'Delete', 'ThisGlobalUser', $this->segment);

    $i->login();
    $i->amOnMailPoetPage('Subscribers');
    $i->waitForListingItemsToLoad();
    $i->clickItemRowActionByItemName($newSubscriberEmail, 'Move to trash');
    $i->changeGroupInListingFilter('trash');
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

    $this->generateSingleSubscriber($newSubscriberEmail, 'Delete', 'ThisGlobalUser', $this->segment);
    $this->generateSingleSubscriber($newSubscriberEmail2, 'Keep', 'ThisSubscriber', $this->segment);

    $i->login();
    $i->amOnMailPoetPage('Subscribers');
    $i->waitForListingItemsToLoad();
    $i->clickItemRowActionByItemName($newSubscriberEmail, 'Move to trash');
    $i->waitForListingItemsToLoad();
    $i->clickItemRowActionByItemName($newSubscriberEmail2, 'Move to trash');
    $i->waitForListingItemsToLoad();
    $i->changeGroupInListingFilter('trash');
    $i->waitForListingItemsToLoad();
    $i->waitForText($newSubscriberEmail);
    $i->clickItemRowActionByItemName($newSubscriberEmail, 'Delete permanently');
    $i->waitForText('1 subscriber was permanently deleted.');
    $i->waitForElementNotVisible('.mailpoet-listing-loading');
    $i->waitForText($newSubscriberEmail2, 20);
    $i->dontSee($newSubscriberEmail);

    $i->seeNoJSErrors();
  }

  public function emptyTrash(\AcceptanceTester $i) {
    $i->wantTo('Delete a subscriber forever');

    $newSubscriberEmail = 'deletesubscriberforever@fakemail.fake';
    $newSubscriberEmail2 = 'deletesubscriberforever2@fakemail.fake';

    $this->generateSingleSubscriber($newSubscriberEmail, 'Delete', 'ThisGlobalUser', $this->segment);
    $this->generateSingleSubscriber($newSubscriberEmail2, 'Keep', 'ThisSubscriber', $this->segment);

    $i->login();
    $i->amOnMailPoetPage('Subscribers');
    $i->waitForListingItemsToLoad();
    $i->clickItemRowActionByItemName($newSubscriberEmail, 'Move to trash');

    $i->changeGroupInListingFilter('trash');
    $i->waitForText($newSubscriberEmail, 20);

    $i->click('[data-automation-id="empty_trash"]');

    $i->waitForText('1 subscriber was permanently deleted.');
    $i->dontSee($newSubscriberEmail);
    $i->changeGroupInListingFilter('all');

    $i->waitForText($newSubscriberEmail2, 20);
    $i->seeNoJSErrors();
  }

  public function addSubscriberToList(\AcceptanceTester $i) {
    $i->wantTo('Add a subscriber to a list');

    $newSubscriberEmail = 'addtolistuser99@fakemail.fake';

    $this->generateMultipleLists();
    $this->generateSingleSubscriber('addtolistuser99@fakemail.fake', 'Add', 'ToAList', $this->segment);

    $i->login();
    $i->amOnMailPoetPage('Subscribers');
    $i->waitForListingItemsToLoad();
    $i->clickItemRowActionByItemName($newSubscriberEmail, 'Edit');
    $i->waitForText('Subscriber');
    $i->waitForElementNotVisible('.mailpoet_form_loading');
    $i->selectOptionInSelect2(self::MULTIPLE_SEGMENT_NAME_COOKING);
    $i->seeNoJSErrors();
    $i->click('Save');
    $i->waitForText(self::SUBSCRIBER_UPDATED_NOTICE);
    $i->waitForText($newSubscriberEmail);
    $i->see(self::MULTIPLE_SEGMENT_NAME_COOKING);

    $i->wantTo('Add a subscriber to a list from the listing page');
    $i->click("[data-automation-id='listing-row-checkbox-2']");
    $i->click('[data-automation-id="action-addToList"]');
    $i->selectOption('#add_to_segment', self::MULTIPLE_SEGMENT_NAME_CAMPING);
    $i->click('.mailpoet-modal-content > button');
    $i->waitForText('1 subscribers were added to list ' . self::MULTIPLE_SEGMENT_NAME_CAMPING . '.');
    $i->waitForListingItemsToLoad();
    $i->waitForText($newSubscriberEmail);
    $i->see(self::MULTIPLE_SEGMENT_NAME_CAMPING);

    $i->wantTo('Check subscriber edit page and the attached lists');
    $i->waitForText('Subscriber');
    $i->clickItemRowActionByItemName($newSubscriberEmail, 'Edit');
    $i->waitForElementNotVisible('.mailpoet_form_loading');
    $i->seeSelectedInSelect2(self::MULTIPLE_SEGMENT_NAME_COOKING);
    $i->seeSelectedInSelect2(self::MULTIPLE_SEGMENT_NAME_CAMPING);
  }

  public function deleteSubscriberFromList(\AcceptanceTester $i) {
    $i->wantTo('Delete a subscriber from a list');

    $newSubscriberEmail = 'deletefromlistuser99@fakemail.fake';

    $this->generateMultipleLists();
    $this->generateSingleSubscriber('deletefromlistuser99@fakemail.fake', 'Delete', 'FromAList', $this->segment);

    $i->login();
    $i->amOnMailPoetPage('Subscribers');
    $i->waitForListingItemsToLoad();
    $i->clickItemRowActionByItemName($newSubscriberEmail, 'Edit');
    $i->waitForText('Subscriber');
    $i->waitForElementNotVisible('.mailpoet_form_loading');
    $i->waitForText(self::SINGLE_SEGMENT_NAME);
    $i->click('.select2-selection__choice__remove');
    $i->seeSelectedInSelect2('');
    $i->click('Save');
    $i->waitForText(self::SUBSCRIBER_UPDATED_NOTICE);
    $i->waitForListingItemsToLoad();
    $i->waitForText($newSubscriberEmail);
    $i->dontSee(self::SINGLE_SEGMENT_NAME, '.mailpoet-tags');

    $i->wantTo('Remove subscriber from list from the listing page');
    $i->clickItemRowActionByItemName($newSubscriberEmail, 'Edit');
    $i->waitForText('Subscriber');
    $i->waitForElementNotVisible('.mailpoet_form_loading');
    $i->selectOptionInSelect2(self::SINGLE_SEGMENT_NAME);
    $i->click('Save');
    $i->waitForText(self::SUBSCRIBER_UPDATED_NOTICE);
    $i->waitForListingItemsToLoad();
    $i->waitForText($newSubscriberEmail);
    $i->click("[data-automation-id='listing-row-checkbox-2']");
    $i->click('[data-automation-id="action-removeFromList"]');
    $i->selectOption('#remove_from_segment', self::SINGLE_SEGMENT_NAME);
    $i->click('.mailpoet-modal-content > button');
    $i->waitForNoticeAndClose('1 subscribers were removed from list ' . self::SINGLE_SEGMENT_NAME);
    $i->waitForListingItemsToLoad();
    $i->waitForText($newSubscriberEmail);
    $i->dontSee(self::SINGLE_SEGMENT_NAME, '.mailpoet-tags');
  }

  public function editSubscriber(\AcceptanceTester $i) {
    $i->wantTo('Edit a subscriber');

    $newSubscriberEmail = 'editglobaluser99@fakemail.fake';
    $unsubscribedMessage = ['xpath' => '//*[@class="description"]'];

    $this->generateMultipleLists();
    $this->generateSingleSubscriber('editglobaluser99@fakemail.fake', 'Edit', 'ThisGlobalUser', $this->segment);

    $i->login();
    $i->amOnMailPoetPage('Subscribers');
    $i->waitForListingItemsToLoad();
    $i->clickItemRowActionByItemName($newSubscriberEmail, 'Edit');
    $i->waitForText('Subscriber');
    $i->fillField(['name' => 'first_name'], 'EditedNew');
    $i->fillField(['name' => 'last_name'], 'EditedGlobalUser');
    $i->selectOption('[data-automation-id="subscriber-status"]', 'Unsubscribed');
    $i->waitForElementNotVisible('.mailpoet_form_loading');
    // we cannot use data-automation attribute because this input is based on guttenberg component
    $i->fillField('.mailpoet-form-field-tags input[type="text"]', 'My tag 1,'); // the comma separates the tag
    $i->fillField('.mailpoet-form-field-tags input[type="text"]', 'My tag 2,');
    $i->fillField('.mailpoet-form-field-tags input[type="text"]', 'My tag 3,');
    $i->selectOption('[data-automation-id="subscriber-status"]', 'Unsubscribed');
    $i->waitForElementNotVisible('.mailpoet_form_loading');
    $i->click('Save');
    $i->waitForElementVisible('[data-automation-id="listing_item_1"]');
    $i->waitForNoticeAndClose(self::SUBSCRIBER_UPDATED_NOTICE);
    $i->see('My tag 1');
    $i->see('My tag 2');
    $i->see('My tag 3');
    $i->clickItemRowActionByItemName($newSubscriberEmail, 'Edit');
    $i->waitForText('Subscriber');
    $i->waitForElementNotVisible('.mailpoet_form_loading');
    $i->seeOptionIsSelected('[data-automation-id="subscriber-status"]', 'Unsubscribed');
    $i->see('Unsubscribed at', $unsubscribedMessage);
    // tags are visible
    $i->see('My tag 1');
    $i->see('My tag 2');
    $i->see('My tag 3');
    $i->selectOptionInSelect2(self::MULTIPLE_SEGMENT_NAME_COOKING);
    $i->selectOptionInSelect2(self::MULTIPLE_SEGMENT_NAME_CAMPING);
    $i->waitForElementClickable('[data-automation-id="subscriber-status"]');
    $i->selectOption('[data-automation-id="subscriber-status"]', 'Subscribed');
    // remove the first tag
    $i->click(Locator::firstElement('.mailpoet-form-field-tags button[aria-label="Remove item"]'));
    $i->click('Save');
    $i->waitForElementVisible('[data-automation-id="listing_item_1"]');
    $i->waitForNoticeAndClose(self::SUBSCRIBER_UPDATED_NOTICE);
    $i->see(self::MULTIPLE_SEGMENT_NAME_COOKING);
    $i->see(self::MULTIPLE_SEGMENT_NAME_CAMPING);
    $i->clickItemRowActionByItemName($newSubscriberEmail, 'Edit');
    $i->waitForText('Subscriber');
    $i->waitForElementNotVisible('.mailpoet_form_loading');
    $i->seeSelectedInSelect2(self::MULTIPLE_SEGMENT_NAME_COOKING);
    $i->seeSelectedInSelect2(self::MULTIPLE_SEGMENT_NAME_CAMPING);
    $i->seeOptionIsSelected('[data-automation-id="subscriber-status"]', 'Subscribed');
    // check that tags are present and one is removed by looking in the hidden data input inside input field
    $i->dontSee('My tag 1');
    $i->see('My tag 2 (1 of 2)');
    $i->see('My tag 3 (2 of 2)');
  }

  public function inactiveSubscribers(\AcceptanceTester $i) {
    $i->wantTo('Check inactive subscribers');

    $this->prepareInactiveSubscribersData();

    $i->login();
    $i->amOnMailPoetPage('Subscribers');

    // Filter inactive subscribers
    $i->changeGroupInListingFilter('inactive');
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
