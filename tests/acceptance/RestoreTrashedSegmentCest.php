<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\DynamicSegment;

class RestoreTrashedSegmentCest {
  public function restoreSegmentFromTrash(\AcceptanceTester $i) {
    $i->wantTo('Restore a segment from trash');

    $segmentTitle = 'Restore Segment From Trash Test';

    $segmentFactory = new DynamicSegment();
    $segment = $segmentFactory
      ->withName($segmentTitle)
      ->withUserRoleFilter('Administrator')
      ->withDeleted()
      ->create();
    $listingAutomationSelector = '[data-automation-id="listing_item_' . $segment->id . '"]';

    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->click('[data-automation-id="dynamic-segments-tab"]');

    $i->waitForElement('[data-automation-id="filters_trash"]', 10);
    $i->click('[data-automation-id="filters_trash"]');
    $i->waitForText($segmentTitle, 10, $listingAutomationSelector);
    $i->clickItemRowActionByItemName($segmentTitle, 'Restore');
    $i->waitForText('1 segment has been restored from the Trash.', 10);
    $i->seeInCurrentURL(urlencode('group[all]'));
    $i->waitForText($segmentTitle, 20, $listingAutomationSelector);
    $i->seeNoJSErrors();
  }
}
