<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Migrator\DbMigration;

class Migration_20260415_090055_Db extends DbMigration {
  public function run(): void {
    $tableName = $this->getTableName(SegmentEntity::class);

    if (!$this->columnExists($tableName, 'confirmation_email_id')) {
      $this->connection->executeQuery(
        "ALTER TABLE `{$tableName}`
          ADD COLUMN `confirmation_email_id` INT(11) UNSIGNED NULL DEFAULT NULL"
      );
    }

    if (!$this->columnExists($tableName, 'confirmation_page_id')) {
      $this->connection->executeQuery(
        "ALTER TABLE `{$tableName}`
          ADD COLUMN `confirmation_page_id` INT(11) UNSIGNED NULL DEFAULT NULL"
      );
    }
  }
}
