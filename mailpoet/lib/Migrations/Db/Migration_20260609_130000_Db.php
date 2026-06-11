<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Migrator\DbMigration;

class Migration_20260609_130000_Db extends DbMigration {
  public function run(): void {
    $subscribersTable = $this->getTableName(SubscriberEntity::class);

    // Index deleted_at so the Trash tab count (`deleted_at IS NOT NULL`) can seek
    // instead of scanning the whole table. Trash is sparse, so without an index a
    // `LIMIT`-capped count never reaches its cap and reads every row; with it, the
    // range scan touches only the few trashed rows — fast even alongside a search.
    if (!$this->indexExists($subscribersTable, 'deleted_at')) {
      $this->connection->executeQuery(
        "ALTER TABLE `{$subscribersTable}`
          ADD INDEX `deleted_at` (`deleted_at`)"
      );
    }
  }
}
