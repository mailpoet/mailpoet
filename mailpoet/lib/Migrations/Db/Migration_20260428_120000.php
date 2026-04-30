<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Migrator\DbMigration;

class Migration_20260428_120000 extends DbMigration {
  public function run(): void {
    $tableName = $this->getTableName(StatisticsUnsubscribeEntity::class);

    // Combine into a single ALTER TABLE so large `statistics_unsubscribes` tables only get one
    // metadata-lock acquisition. Column placement is left to MySQL (no AFTER clauses) so MySQL 8.0+
    // can use the INSTANT algorithm for the column adds.
    $alterations = [];
    if (!$this->columnExists($tableName, 'reason')) {
      $alterations[] = 'ADD COLUMN `reason` varchar(80) NULL DEFAULT NULL';
    }
    if (!$this->columnExists($tableName, 'reason_text')) {
      $alterations[] = 'ADD COLUMN `reason_text` text NULL DEFAULT NULL';
    }
    if (!$this->columnExists($tableName, 'reason_submitted_at')) {
      $alterations[] = 'ADD COLUMN `reason_submitted_at` timestamp NULL DEFAULT NULL';
    }
    if (!$this->indexExists($tableName, 'newsletter_id_reason')) {
      $alterations[] = 'ADD INDEX `newsletter_id_reason` (`newsletter_id`, `reason`)';
    }

    if ($alterations === []) {
      return;
    }

    $this->connection->executeStatement(
      "ALTER TABLE `{$tableName}` " . implode(', ', $alterations)
    );
  }
}
