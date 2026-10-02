<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Migrator\DbMigration;

class Migration_20261002_093000_Db extends DbMigration {
  public function run(): void {
    $subscribersTable = $this->getTableName(SubscriberEntity::class);
    if ($this->columnExists($subscribersTable, 'source_url')) {
      return;
    }
    $this->connection->executeStatement(
      "ALTER TABLE `{$subscribersTable}` ADD COLUMN `source_url` text NULL"
    );
  }
}
