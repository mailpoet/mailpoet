<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Migrator\DbMigration;

class Migration_20260610_120000_Db extends DbMigration {
  public function run(): void {
    $subscriberSegmentTable = $this->getTableName(SubscriberSegmentEntity::class);

    // Index (segment_id, status, subscriber_id) to speed up the subscriber
    // listing under a static-list filter. Those queries join the membership
    // table by segment_id and constrain on the per-list status (ss.status),
    // and the status tabs run one such count per status. Without this index each
    // count scans the whole segment's memberships (tens of seconds on large
    // lists); with it the join seeks by segment_id, filters on status, and the
    // trailing subscriber_id covers the join so the lookup stays index-only.
    if (!$this->indexExists($subscriberSegmentTable, 'segment_id_status')) {
      $this->connection->executeQuery(
        "ALTER TABLE `{$subscriberSegmentTable}`
          ADD INDEX `segment_id_status` (`segment_id`, `status`, `subscriber_id`)"
      );
    }
  }
}
