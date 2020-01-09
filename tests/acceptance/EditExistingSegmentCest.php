<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\DynamicSegment;

class EditExistingSegmentCest {
  public function editUserRoleSegment(\AcceptanceTester $i) {
    $i->wantTo('Edit an existing WP user role segment');

    $segmentTitle = 'Edit User Role Segment Test';
    $segmentEditedTitle = 'Edit User Role Segment Test Edited';

    $segmentFactory = new DynamicSegment();
    $segment = $segmentFactory
      ->withName($segmentTitle)
      ->withUserRoleFilter('Administrator')
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Segments');
    $listingAutomationSelector = '[data-automation-id="listing_item_' . $segment->id . '"]';
    $i->waitForText($segmentTitle, 10, $listingAutomationSelector);
    $i->clickItemRowActionByItemName($segmentTitle, 'Edit');

    $i->seeInCurrentUrl('mailpoet-dynamic-segments#/edit/' . $segment->id);
    $i->waitForElementNotVisible('.mailpoet_form_loading');
    $i->fillField(['name' => 'name'], $segmentEditedTitle);
    $i->fillField(['name' => 'description'], 'Lorem ipsum dolor amed edited');
    $i->selectOption('form select[name=segmentType]', 'WordPress user roles');
    $i->selectOption('form select[name=wordpressRole]', 'Editor');
    $i->click('Save');

    $i->waitForText($segmentEditedTitle, 20, $listingAutomationSelector);
    $i->seeNoJSErrors();
  }
}
