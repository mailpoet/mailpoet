<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\DynamicSegment;

class DeleteTrashedSegmentCest {
  public function deleteSegmentFromTrash(\AcceptanceTester $I) {
    $I->wantTo('Delete a segment from trash');

    $segment_title = 'Delete Segment From Trash Test';

    $segment_factory = new DynamicSegment();
    $segment = $segment_factory
      ->withName($segment_title)
      ->withUserRoleFilter('Administrator')
      ->withDeleted()
      ->create();
    $listing_automation_selector = '[data-automation-id="listing_item_' . $segment->id . '"]';

    $I->login();
    $I->amOnMailpoetPage('Segments');
    $I->waitForElement('[data-automation-id="filters_trash"]', 10);
    $I->click('[data-automation-id="filters_trash"]');
    $I->waitForText($segment_title, 10, $listing_automation_selector);
    $I->clickItemRowActionByItemName($segment_title, 'Delete permanently');
    $I->waitForText('1 segment was permanently deleted.', 10);
    $I->dontSeeElement($listing_automation_selector);
    $I->seeNoJSErrors();
  }
}
