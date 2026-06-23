<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\ScheduledTaskQueuedSubscriberEntity;
use MailPoet\Migrator\DbMigration;

class Migration_20260617_120000_Db extends DbMigration {
  public function run(): void {
    global $wpdb;

    $table = $this->getTableName(ScheduledTaskQueuedSubscriberEntity::class);
    if ($this->tableExists($table)) {
      return;
    }

    $charsetCollate = $wpdb->get_charset_collate();
    $this->connection->executeStatement("
      CREATE TABLE `{$table}` (
        `task_id` int(11) unsigned NOT NULL,
        `subscriber_id` int(11) unsigned NOT NULL,
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`task_id`, `subscriber_id`)
      ) {$charsetCollate}
    ");
  }
}
