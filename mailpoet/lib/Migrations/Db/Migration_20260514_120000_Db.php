<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Migrator\DbMigration;

class Migration_20260514_120000_Db extends DbMigration {
  public function run(): void {
    $segmentsTable = $this->getTableName(SegmentEntity::class);
    $columnName = 'public_description';

    if (!$this->columnExists($segmentsTable, $columnName)) {
      $this->connection->executeStatement("
        ALTER TABLE `{$segmentsTable}`
        ADD COLUMN `{$columnName}` text NULL
      ");
    }

    $this->connection->executeStatement("
      UPDATE `{$segmentsTable}`
      SET `{$columnName}` = ''
      WHERE `{$columnName}` IS NULL
    ");
  }
}
