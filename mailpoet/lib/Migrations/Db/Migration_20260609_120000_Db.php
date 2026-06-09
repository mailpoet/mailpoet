<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Migrator\DbMigration;

class Migration_20260609_120000_Db extends DbMigration {
  public function run(): void {
    $subscribersTable = $this->getTableName(SubscriberEntity::class);

    // Index created_at so the subscribers listing can sort by "Created on"
    // without a filesort. InnoDB appends the primary key to secondary indexes,
    // so this single-column index covers `ORDER BY created_at, id` as well.
    if (!$this->indexExists($subscribersTable, 'created_at')) {
      $this->connection->executeQuery(
        "ALTER TABLE `{$subscribersTable}`
          ADD INDEX `created_at` (`created_at`)"
      );
    }
  }
}
