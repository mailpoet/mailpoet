<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Migrator\DbMigration;

class Migration_20261015_120000_Db extends DbMigration {
  public function run(): void {
    $subscribersTable = $this->getTableName(SubscriberEntity::class);

    // Indexes for sorting the subscribers listing by engagement score and by
    // last subscribed date. Like `deleted_at_created`, they lead with
    // `deleted_at` so `deleted_at IS NULL` pins the first column and rows come
    // out already ordered, without a filesort and without the index-merge plan
    // a single-column index would allow. Sorting by email needs no new index
    // (it uses the UNIQUE email index) and neither does status (it uses
    // `status_deleted_at`).
    $alterations = [];

    if (!$this->indexExists($subscribersTable, 'deleted_at_engagement_score')) {
      $alterations[] = 'ADD INDEX `deleted_at_engagement_score` (`deleted_at`, `engagement_score`)';
    }

    if (!$this->indexExists($subscribersTable, 'deleted_at_last_subscribed')) {
      $alterations[] = 'ADD INDEX `deleted_at_last_subscribed` (`deleted_at`, `last_subscribed_at`)';
    }

    if ($alterations === []) {
      return;
    }

    $this->connection->executeStatement(
      "ALTER TABLE `{$subscribersTable}` " . implode(', ', $alterations)
    );
  }
}
