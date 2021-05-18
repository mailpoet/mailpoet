<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\CustomField;
use MailPoet\Test\DataFactories\Subscriber;

class MailPoetCustomFieldSegmentCest {
  public function _before() {
    $customField = (new CustomField())
      ->withName('Text custom field');
    (new Subscriber())
      ->withEmail('test1@example.com')
      ->create();
    $subscriber2 = (new Subscriber())
      ->withEmail('test2@example.com')
      ->create();
    $subscriber3 = (new Subscriber())
      ->withEmail('test3@example.com')
      ->create();
    $customField->withSubscriber($subscriber2->id(), 'some value1 here');
    $customField->withSubscriber($subscriber3->id(), 'some value2 here');
    $customField->create();
  }

  public function createSegment(\AcceptanceTester $i) {
    $i->wantTo('Create a new MailPoet custom fields segment');
    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->click('[data-automation-id="new-segment"]');
    $segmentTitle = 'MailPoet custom fields segment';
    $i->fillField(['name' => 'name'], $segmentTitle);
    $i->fillField(['name' => 'description'], 'description');
    $i->selectOptionInReactSelect('MailPoet custom field', '[data-automation-id="select-segment-action"]');
    $i->waitForElementVisible('[data-automation-id="select-custom-field"]');
    $i->selectOptionInReactSelect('Text custom field', '[data-automation-id="select-custom-field"]');
    $i->waitForElementVisible('[data-automation-id="text-custom-field-operator"]');
    $i->selectOption('[data-automation-id="text-custom-field-operator"]', 'contains');
    $i->fillField('[data-automation-id="text-custom-field-value"]', 'value1');
    $i->click('Save');

    $i->wantTo('Edit the segment');
    $i->amOnMailpoetPage('Lists');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText($segmentTitle);
    $i->clickItemRowActionByItemName($segmentTitle, 'Edit');
    $i->waitForElementVisible('[data-automation-id="text-custom-field-operator"]');
    $i->seeInField('[data-automation-id="text-custom-field-operator"]', 'contains');
    $i->seeInField('[data-automation-id="text-custom-field-value"]', 'value1');
    $i->waitForText('This segment has 1 subscribers.');
    $i->seeNoJSErrors();

    $i->wantTo('Check subscribers of the segment');
    $i->amOnMailpoetPage('Lists');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText($segmentTitle);
    $i->clickItemRowActionByItemName($segmentTitle, 'View Subscribers');
    $i->seeInCurrentUrl('mailpoet-subscribers#');
    $i->see($segmentTitle, ['css' => 'select[name=segment]']);
    $i->see('test2@example.com');
    $i->dontSee('test1@example.com');
    $i->dontSee('test3@example.com');
  }
}
