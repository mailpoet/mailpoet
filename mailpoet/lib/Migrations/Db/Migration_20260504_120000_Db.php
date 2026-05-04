<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Migrator\DbMigration;

class Migration_20260504_120000_Db extends DbMigration {
  public function run(): void {
    $subscribersTable = $this->getTableName(SubscriberEntity::class);

    $columnType = $this->connection->fetchOne(
      "SELECT COLUMN_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = 'source'",
      [$subscribersTable]
    );

    if (is_string($columnType) && strpos($columnType, "'wordpress_user_deleted'") !== false) {
      return;
    }

    $this->connection->executeQuery(
      "ALTER TABLE `{$subscribersTable}`
        MODIFY `source` enum(
          'form',
          'imported',
          'administrator',
          'api',
          'wordpress_user',
          'wordpress_user_deleted',
          'woocommerce_user',
          'woocommerce_checkout',
          'unknown'
        ) DEFAULT 'unknown'"
    );
  }
}
