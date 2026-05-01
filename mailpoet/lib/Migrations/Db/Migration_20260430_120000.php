<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Migrator\DbMigration;

class Migration_20260430_120000 extends DbMigration {
  public function run(): void {
    $subscribersTable = $this->getTableName(SubscriberEntity::class);

    $alterations = [];
    if (!$this->columnExists($subscribersTable, 'time_zone')) {
      $alterations[] = 'ADD COLUMN `time_zone` varchar(64) NULL DEFAULT NULL';
    }
    if (!$this->columnExists($subscribersTable, 'time_zone_source')) {
      $alterations[] = 'ADD COLUMN `time_zone_source` varchar(32) NULL DEFAULT NULL';
    }
    if (!$this->columnExists($subscribersTable, 'time_zone_confidence')) {
      $alterations[] = 'ADD COLUMN `time_zone_confidence` int NULL DEFAULT NULL';
    }
    if (!$this->columnExists($subscribersTable, 'time_zone_updated_at')) {
      $alterations[] = 'ADD COLUMN `time_zone_updated_at` timestamp NULL DEFAULT NULL';
    }

    if ($alterations === []) {
      return;
    }

    $this->connection->executeStatement(
      "ALTER TABLE `{$subscribersTable}` " . implode(', ', $alterations)
    );
  }
}
