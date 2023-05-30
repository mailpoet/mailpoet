<?php declare(strict_types = 1);

namespace MailPoet\Migrations;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Migrator\Migration;

class Migration_20230503_210945 extends Migration {
  public function run(): void {
    $subscribersTable = $this->getTableName(SubscriberEntity::class);
    if (!$this->indexExists($subscribersTable, 'first_name')) {
      $this->connection->executeQuery(
        "ALTER TABLE `{$subscribersTable}`
          ADD INDEX `first_name` (`first_name`(10))"
      );
    }
    if (!$this->indexExists($subscribersTable, 'last_name')) {
      $this->connection->executeQuery(
        "ALTER TABLE `{$subscribersTable}`
          ADD INDEX `last_name` (`last_name`(10))"
      );
    }
  }
}
