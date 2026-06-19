<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\DynamicSegment;

/**
 * Date-range filtering of the dynamic segments listing is exercised at the SQL
 * level in DynamicSegmentsListingRepositoryTest. This cest guards the listing's
 * legacy deep link, which shares the `extraParams` callback the native filters
 * feed into.
 *
 * @group frontend
 */
class DynamicSegmentsListingFiltersCest {
  public function preservesLegacyOffsetDeepLink(\AcceptanceTester $i) {
    $i->wantTo('Keep the legacy dynamic segments offset hash route working');

    $segmentNames = ['Legacy Offset Segment A', 'Legacy Offset Segment B', 'Legacy Offset Segment C'];
    foreach ($segmentNames as $name) {
      (new DynamicSegment())->withName($name)->withUserRoleFilter('editor')->create();
    }

    $i->login();
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-segments#/segments/limit[2]/offset[2]');
    $i->waitForElement('[data-automation-id="dynamic_segments_listing"]');
    $i->seeInCurrentUrl(urlencode('offset[2]'));
    $i->seeNoJSErrors();
  }
}
