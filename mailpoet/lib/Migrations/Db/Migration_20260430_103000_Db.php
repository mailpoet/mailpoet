<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Migrator\DbMigration;

class Migration_20260430_103000_Db extends DbMigration {
  public function run(): void {
    $customFieldsTable = $this->getTableName(CustomFieldEntity::class);
    if (!$this->columnExists($customFieldsTable, 'deleted_at')) {
      $this->connection->executeQuery(
        "ALTER TABLE `{$customFieldsTable}`
          ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL"
      );
    }
  }
}
