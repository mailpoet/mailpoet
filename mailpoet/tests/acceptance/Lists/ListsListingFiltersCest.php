<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Segment;

/**
 * Date-range and engagement-score filtering of the lists listing is exercised
 * at the SQL level in SegmentListingRepositoryTest. This cest guards the
 * listing's legacy trash deep link.
 *
 * @group frontend
 */
class ListsListingFiltersCest {
  public function preservesLegacyTrashDeepLink(\AcceptanceTester $i) {
    $i->wantTo('Keep the legacy lists trash hash route working');

    $trashedListName = 'Legacy Trash Deep Link List';
    (new Segment())->withName($trashedListName)->withDeleted()->create();

    $i->login();
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-lists#/lists/group[trash]');
    $i->waitForText($trashedListName, 5, '[data-automation-id="segments_listing"]');
    $i->seeInCurrentUrl(urlencode('group[trash]'));
    $i->seeNoJSErrors();
  }
}
