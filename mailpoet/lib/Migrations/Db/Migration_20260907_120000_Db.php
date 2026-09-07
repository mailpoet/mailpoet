<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\LogEntity;
use MailPoet\Migrator\DbMigration;

/**
 * The newsletters listing and the log listing filter the log table by `name`, which had no index,
 * so every lookup scanned the whole table. Sites that ran with verbose logging accumulated
 * millions of rows and the newsletters listing timed out.
 *
 * Some affected sites already added an index on `name` by hand under their own name. Any index
 * that starts with `name` serves the lookups, so the migration checks the leading column instead
 * of the index name to avoid building a redundant index on a multi-GB table.
 */
class Migration_20260907_120000_Db extends DbMigration {
  private const INDEX_NAME = 'idx_log_name_created_at';

  public function run(): void {
    $logTable = $this->getTableName(LogEntity::class);

    if ($this->indexStartingWithColumnExists($logTable, 'name')) {
      return;
    }

    $this->connection->executeStatement(
      "CREATE INDEX `" . self::INDEX_NAME . "` ON `{$logTable}` (`name`, `created_at`)"
    );
  }

  private function indexStartingWithColumnExists(string $tableName, string $columnName): bool {
    global $wpdb;
    $count = $this->connection->fetchOne(
      "SELECT COUNT(*)
       FROM information_schema.statistics
       WHERE table_schema = COALESCE(DATABASE(), :database)
         AND table_name = :table
         AND column_name = :column
         AND seq_in_index = 1
         AND index_type = 'BTREE'",
      [
        'database' => $wpdb->dbname,
        'table' => $tableName,
        'column' => $columnName,
      ]
    );
    return is_numeric($count) && (int)$count > 0;
  }
}
