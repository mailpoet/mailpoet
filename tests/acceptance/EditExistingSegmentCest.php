<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\DynamicSegment;

class EditExistingSegmentCest {
  public function editUserRoleSegment(\AcceptanceTester $I) {
    $I->wantTo('Edit an existing WP user role segment');

    $segment_title = 'Edit User Role Segment Test';
    $segment_edited_title = 'Edit User Role Segment Test Edited';

    $segment_factory = new DynamicSegment();
    $segment = $segment_factory
      ->withName($segment_title)
      ->withUserRoleFilter('Administrator')
      ->create();

    $I->login();
    $I->amOnMailpoetPage('Segments');
    $listing_automation_selector = '[data-automation-id="listing_item_' . $segment->id . '"]';
    $I->waitForText($segment_title, 10, $listing_automation_selector);
    $I->clickItemRowActionByItemName($segment_title, 'Edit');

    $I->seeInCurrentUrl('mailpoet-dynamic-segments#/edit/' . $segment->id);
    $I->waitForElementNotVisible('.mailpoet_form_loading');
    $I->fillField(['name' => 'name'], $segment_edited_title);
    $I->fillField(['name' => 'description'], 'Lorem ipsum dolor amed edited');
    $I->selectOption('form select[name=segmentType]', 'WordPress user roles');
    $I->selectOption('form select[name=wordpressRole]', 'Editor');
    $I->click('Save');

    $I->waitForText($segment_edited_title, 20, $listing_automation_selector);
    $I->seeNoJSErrors();
  }
}
