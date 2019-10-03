<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\DynamicSegment;

class RestoreTrashedSegmentCest {
  function restoreSegmentFromTrash(\AcceptanceTester $I) {
    $I->wantTo('Restore a segment from trash');

    $segment_title = 'Restore Segment From Trash Test';

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
    $I->clickItemRowActionByItemName($segment_title, 'Restore');
    $I->waitForText('1 segment has been restored from the Trash.', 10);
    $I->seeInCurrentURL(urlencode('group[all]'));
    $I->waitForText($segment_title, 20, $listing_automation_selector);
    $I->seeNoJSErrors();
  }
}
