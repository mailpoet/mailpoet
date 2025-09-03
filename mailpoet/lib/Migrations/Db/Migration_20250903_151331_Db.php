<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Migrator\DbMigration;

class Migration_20250903_151331_Db extends DbMigration {
  public function run(): void {
    $logTable = $this->getTableName(\MailPoet\Entities\LogEntity::class);
    $indexName = 'idx_log_created_at_id';

    if ($this->indexExists($logTable, $indexName)) {
      return;
    }

    $this->connection->executeQuery(
      "CREATE INDEX `{$indexName}` ON `{$logTable}` (`created_at`, `id`)"
    );
  }
}
