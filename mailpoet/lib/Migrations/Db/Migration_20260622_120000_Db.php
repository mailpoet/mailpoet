<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Migrator\DbMigration;

class Migration_20260622_120000_Db extends DbMigration {
  public function run(): void {
    $subscribersTable = $this->getTableName(SubscriberEntity::class);

    $alterations = [];

    // Denormalized count of the subscriber's subscribed memberships in
    // non-deleted segments. It powers the "Subscribers without a list" filter
    // and its per-status counts: those used to run an anti-join over the whole
    // subscribers table (tens of minutes on large installs). With this column
    // the count becomes `WHERE segments_count = 0`. Defaults to 0; the real
    // values are populated by the SubscribersSegmentsCountBackfill worker and
    // kept in sync by SegmentsCountRecalculator. Reads only trust the column
    // once the backfill has completed (see SubscribersCountsController).
    if (!$this->columnExists($subscribersTable, 'segments_count')) {
      $alterations[] = 'ADD COLUMN `segments_count` INT UNSIGNED NOT NULL DEFAULT 0';
    }

    // Range on segments_count = 0, then status and deleted_at cover the
    // per-status breakdown so the count query stays index-only.
    if (!$this->indexExists($subscribersTable, 'segments_count_status_deleted_at')) {
      $alterations[] = 'ADD INDEX `segments_count_status_deleted_at` (`segments_count`, `status`, `deleted_at`)';
    }

    if ($alterations === []) {
      return;
    }

    $this->connection->executeStatement(
      "ALTER TABLE `{$subscribersTable}` " . implode(', ', $alterations)
    );
  }
}
