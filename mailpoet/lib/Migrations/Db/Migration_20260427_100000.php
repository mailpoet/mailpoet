<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Migrator\DbMigration;

class Migration_20260427_100000 extends DbMigration {
  public function run(): void {
    $subscribersTable = $this->getTableName(SubscriberEntity::class);
    if ($this->columnExists($subscribersTable, 'last_confirmation_email_sent_at')) {
      return;
    }

    $this->connection->executeQuery(
      "ALTER TABLE `{$subscribersTable}`
        ADD COLUMN `last_confirmation_email_sent_at` TIMESTAMP NULL DEFAULT NULL"
    );
  }
}
