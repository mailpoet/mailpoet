<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Migrator\DbMigration;

class Migration_20260610_120000_Db extends DbMigration {
  public function run(): void {
    $subscriberSegmentTable = $this->getTableName(SubscriberSegmentEntity::class);

    // Index (segment_id, status, subscriber_id) so per-list status counts can seek
    // instead of scanning the whole membership table. The listing's status tabs
    // under a list filter previously ran a COUNT(DISTINCT) per status, each a full
    // scan of the segment's memberships (tens of seconds on large lists). With this
    // index a sparse status (e.g. unsubscribed) becomes a direct seek, and the
    // dominant "subscribed" mass is read index-only as a plain COUNT — so the tab
    // strip is derived from cheap index reads rather than million-row joins. The
    // trailing subscriber_id makes the index covering for the membership lookups.
    if (!$this->indexExists($subscriberSegmentTable, 'segment_id_status')) {
      $this->connection->executeQuery(
        "ALTER TABLE `{$subscriberSegmentTable}`
          ADD INDEX `segment_id_status` (`segment_id`, `status`, `subscriber_id`)"
      );
    }
  }
}
