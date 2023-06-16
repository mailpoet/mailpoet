<?php declare(strict_types = 1);

namespace MailPoet\Migrations;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Migrator\Migration;

class Migration_20230616_130221 extends Migration {
  public function run(): void {
    $tableName = $this->getTableName(NewsletterEntity::class);
    if (!$this->columnExists($tableName, 'wp_post_id')) {
      $this->connection->executeStatement("
        ALTER TABLE {$tableName}
        ADD wp_post_id int NULL
      ");
    }

    if (!$this->indexExists($tableName, 'wp_post_id')) {
      $this->connection->executeQuery(
        "ALTER TABLE `{$tableName}`
          ADD INDEX `wp_post_id` (`wp_post_id`)"
      );
    }
  }
}
