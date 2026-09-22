<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Doctrine\WPDB\Connection;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Migrator\DbMigration;

/**
 * Widens the subscriber_id index so the Unknown and Dormant filters can count a
 * subscriber's sends from the index alone, instead of reading each of that
 * subscriber's sent rows from the table. The engagement score also filters on
 * sent_with_tracking, so it still reads the rows, but only those in its window.
 *
 * The new index starts with subscriber_id, so it serves every query the old one
 * did. Adding it and dropping the old one in one statement keeps the table from
 * ever having neither. The SQLite integration used by WordPress Playground may
 * not accept both in one statement, so there the old index is left in place.
 */
class Migration_20260922_082011_Db extends DbMigration {
  private const NEW_INDEX = 'subscriber_id_sent_at_newsletter_id';
  private const OLD_INDEX = 'subscriber_id';

  public function run(): void {
    $table = $this->getTableName(StatisticsNewsletterEntity::class);
    if ($this->indexExists($table, self::NEW_INDEX)) {
      return;
    }

    $dropOldIndex = !Connection::isSQLite() && $this->indexExists($table, self::OLD_INDEX)
      ? ', DROP INDEX `' . self::OLD_INDEX . '`'
      : '';
    $this->connection->executeStatement(
      "ALTER TABLE `{$table}` ADD INDEX `" . self::NEW_INDEX . "` (`subscriber_id`, `sent_at`, `newsletter_id`){$dropOldIndex}"
    );
  }
}
