<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Migrator\DbMigration;

class Migration_20260428_120000 extends DbMigration {
  public function run(): void {
    $tableName = $this->getTableName(StatisticsUnsubscribeEntity::class);

    if (!$this->columnExists($tableName, 'reason')) {
      $this->connection->executeStatement("
        ALTER TABLE `{$tableName}`
          ADD COLUMN `reason` varchar(80) NULL DEFAULT NULL AFTER `method`
      ");
    }

    if (!$this->columnExists($tableName, 'reason_text')) {
      $this->connection->executeStatement("
        ALTER TABLE `{$tableName}`
          ADD COLUMN `reason_text` text NULL DEFAULT NULL AFTER `reason`
      ");
    }

    if (!$this->columnExists($tableName, 'reason_submitted_at')) {
      $this->connection->executeStatement("
        ALTER TABLE `{$tableName}`
          ADD COLUMN `reason_submitted_at` timestamp NULL DEFAULT NULL AFTER `reason_text`
      ");
    }
  }
}
