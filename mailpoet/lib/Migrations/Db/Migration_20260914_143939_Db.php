<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Migrator\DbMigration;

/**
 * Records per recipient whether the email went out with the open pixel and
 * tracked links, so open and click rates can be divided by the recipients we
 * could measure.
 *
 * Existing rows default to 1, which keeps the rates they show today until the
 * sends that were not tracked are marked. The column and the index go in one
 * ALTER so the table is rebuilt at most once.
 */
class Migration_20260914_143939_Db extends DbMigration {
  public function run(): void {
    $table = $this->getTableName(StatisticsNewsletterEntity::class);

    $changes = [];
    if (!$this->columnExists($table, 'sent_with_tracking')) {
      $changes[] = 'ADD COLUMN `sent_with_tracking` tinyint(1) NOT NULL DEFAULT 1';
    }
    if (!$this->indexExists($table, 'newsletter_id_sent_with_tracking')) {
      // queue_id lets the campaign count join its queue from the index alone.
      $changes[] = 'ADD INDEX `newsletter_id_sent_with_tracking` (`newsletter_id`, `sent_with_tracking`, `queue_id`)';
    }
    if (!$changes) {
      return;
    }

    $this->connection->executeStatement("ALTER TABLE `{$table}` " . implode(', ', $changes));
  }
}
