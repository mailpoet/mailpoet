<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\SettingEntity;
use MailPoet\Migrator\DbMigration;

/**
 * Updates the logging level to 'errors' if it is 'everything'
 * and prunes the log table if needed.
 *
 * This is a one time fix for users who had logging level set to 'everything' and table was filled
 * with email editor logs.
 */
class Migration_20250926_153050_Db extends DbMigration {
  public function run(): void {
    $this->updateLoggingLevel();
    $this->pruneLogTableIfNeeded();
  }

  private function updateLoggingLevel(): void {
    $settingsTable = $this->getTableName(SettingEntity::class);

    if (!$this->tableExists($settingsTable)) {
      return;
    }

    // Check if logging level is 'everything'
    $loggingLevel = $this->connection->fetchOne("
      SELECT value
      FROM {$settingsTable}
      WHERE name = 'logging'
    ");

    if ($loggingLevel !== 'everything') {
      return;
    }

    // Update logging level from 'everything' to 'errors'
    $this->connection->executeStatement("
      UPDATE {$settingsTable}
      SET value = 'errors'
      WHERE name = 'logging' AND value = 'everything'
    ");
  }

  private function pruneLogTableIfNeeded(): void {
    $settingsTable = $this->getTableName(SettingEntity::class);
    $logTable = $this->getTableName(\MailPoet\Entities\LogEntity::class);

    if (!$this->tableExists($settingsTable) || !$this->tableExists($logTable)) {
      return;
    }

    // Check if pruning flag exists and is true
    $flagResult = $this->connection->fetchOne("
      SELECT value
      FROM {$settingsTable}
      WHERE name = 'log_table_pruned_migration_20250926'
    ");

    // If flag doesn't exist or is not '1', proceed with pruning
    if ($flagResult !== '1') {
      // Truncate the log table
      try {
        // Prefer fast path
        $this->connection->executeStatement("TRUNCATE TABLE {$logTable}");
      } catch (\Throwable $e) {
        // Fallback for environments without TRUNCATE privileges
        $this->connection->executeStatement("DELETE FROM {$logTable}");
      }

      // Set the pruning flag to true
      $this->connection->executeStatement("
        INSERT INTO {$settingsTable} (name, value, created_at, updated_at)
        VALUES ('log_table_pruned_migration_20250926', '1', NOW(), NOW())
        ON DUPLICATE KEY UPDATE
          value = '1',
          updated_at = NOW()
      ");
    }
  }
}
