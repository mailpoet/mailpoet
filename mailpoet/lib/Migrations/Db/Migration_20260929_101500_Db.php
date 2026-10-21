<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Migrator\DbMigration;

class Migration_20260929_101500_Db extends DbMigration {
  public function run(): void {
    $subscribersTable = $this->getTableName(SubscriberEntity::class);
    if ($this->columnExists($subscribersTable, 'source_plugin')) {
      return;
    }
    $this->connection->executeStatement(
      "ALTER TABLE `{$subscribersTable}` ADD COLUMN `source_plugin` varchar(191) NULL DEFAULT NULL"
    );
  }
}
