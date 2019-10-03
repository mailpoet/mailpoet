<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\DynamicSegment;
use MailPoet\Test\DataFactories\Settings;

class TrashExistingSegmentCest {

  function _before() {
    (new Settings())->withWooCommerceListImportPageDisplayed(true);
    (new Settings())->withCookieRevenueTrackingDisabled();
  }

  function moveSegmentToTrash(\AcceptanceTester $I) {
    $I->wantTo('Move an existing segment to trash');

    $segment_title = 'Move Segment To Trash Test';

    $segment_factory = new DynamicSegment();
    $segment = $segment_factory
      ->withName($segment_title)
      ->withUserRoleFilter('Administrator')
      ->create();

    $I->login();
    $I->amOnMailpoetPage('Segments');
    $listing_automation_selector = '[data-automation-id="listing_item_' . $segment->id . '"]';
    $I->waitForText($segment_title, 10, $listing_automation_selector);
    $I->clickItemRowActionByItemName($segment_title, 'Move to trash');
    $I->waitForText('1 segment was moved to the trash.', 10);
    $I->click('[data-automation-id="filters_trash"]');
    $I->waitForText($segment_title, 20, $listing_automation_selector);
    $I->seeNoJSErrors();
  }
}
