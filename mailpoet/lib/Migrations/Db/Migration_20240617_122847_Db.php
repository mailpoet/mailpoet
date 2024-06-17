<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Migrator\DbMigration;

class Migration_20240617_122847_Db extends DbMigration {
  public function run(): void {
    $scheduledTasksTable = $this->getTableName(ScheduledTaskEntity::class);
    $newColumn = 'cancelled_at';
    if ($this->columnExists($scheduledTasksTable, $newColumn)) {
      return;
    }

    $this->connection->executeQuery(
      "ALTER TABLE `{$scheduledTasksTable}`
        ADD COLUMN `{$newColumn}` TIMESTAMP NULL DEFAULT NULL"
    );
  }
}
