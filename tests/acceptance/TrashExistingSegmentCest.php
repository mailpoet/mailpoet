<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\DynamicSegment;
use MailPoet\Test\DataFactories\Settings;

class TrashExistingSegmentCest {
  public function _before() {
    (new Settings())->withWooCommerceListImportPageDisplayed(true);
    (new Settings())->withCookieRevenueTrackingDisabled();
  }

  public function moveSegmentToTrash(\AcceptanceTester $i) {
    $i->wantTo('Move an existing segment to trash');

    $segmentTitle = 'Move Segment To Trash Test';

    $segmentFactory = new DynamicSegment();
    $segment = $segmentFactory
      ->withName($segmentTitle)
      ->withUserRoleFilter('Administrator')
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $listingAutomationSelector = '[data-automation-id="listing_item_' . $segment->id . '"]';
    $i->waitForText($segmentTitle, 10, $listingAutomationSelector);
    $i->clickItemRowActionByItemName($segmentTitle, 'Move to trash');
    $i->waitForText('1 segment was moved to the trash.', 10);
    $i->click('[data-automation-id="filters_trash"]');
    $i->waitForText($segmentTitle, 20, $listingAutomationSelector);
    $i->seeNoJSErrors();
  }
}
