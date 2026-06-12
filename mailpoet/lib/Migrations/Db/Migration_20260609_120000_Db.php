<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Migrator\DbMigration;

class Migration_20260609_120000_Db extends DbMigration {
  public function run(): void {
    $subscribersTable = $this->getTableName(SubscriberEntity::class);

    // Index (deleted_at, created_at) so the subscribers listing's default view
    // (non-trashed, newest first) reads its rows straight from the index:
    // `deleted_at IS NULL` pins the first column, so entries come out ordered
    // by created_at — and by id within ties, since InnoDB appends the primary
    // key to secondary indexes. Without it the query has no usable index at
    // all (every other index leads with another column) and degrades to a
    // full-table scan plus a filesort of every non-trashed subscriber.
    //
    // A single-column created_at index would serve the same query slightly
    // worse (it scans trashed rows too), but the important difference shows
    // under `deleted_at IS NULL` filtering: a single-column index is then
    // fully bound, which qualifies it as a rowid-ordered scan that the
    // optimizer can combine into an index-merge intersect with other
    // status/deleted_at indexes — a plan that materializes and filesorts
    // millions of rows on large sites. With created_at as an unbound second
    // column, this index never qualifies, so that plan cannot be built from
    // it. The deleted_at prefix also covers Trash counts
    // (`deleted_at IS NOT NULL`) as an index-only range scan.
    if (!$this->indexExists($subscribersTable, 'deleted_at_created')) {
      $this->connection->executeQuery(
        "ALTER TABLE `{$subscribersTable}`
          ADD INDEX `deleted_at_created` (`deleted_at`, `created_at`)"
      );
    }
  }
}
