<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Migrator\DbMigration;

class Migration_20260709_120000_Db extends DbMigration {
  public function run(): void {
    $statisticsNewslettersTable = $this->getTableName(StatisticsNewsletterEntity::class);

    // The campaign stats "Unopened" listing filters recipients of a
    // subscriber-timezone campaign with `newsletter_id = ? AND queue_id IN (...)`.
    // The other statistics tables already carry a queue_id index, but here the
    // best existing index is (newsletter_id), which reads every recipient of
    // the newsletter and discards the rows outside the selected batches.
    // Leading with (newsletter_id, queue_id) binds both predicates, so only
    // the selected batches are read; the trailing subscriber_id serves the
    // per-row anti-join lookup against statistics_opens (subscriber_id,
    // newsletter_id) from the index itself.
    if (!$this->indexExists($statisticsNewslettersTable, 'newsletter_id_queue_id_subscriber_id')) {
      $this->connection->executeQuery(
        "ALTER TABLE `{$statisticsNewslettersTable}`
          ADD INDEX `newsletter_id_queue_id_subscriber_id` (`newsletter_id`, `queue_id`, `subscriber_id`)"
      );
    }
  }
}
