<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Migrator\DbMigration;

/**
 * Records, per recipient, whether we were allowed to track them at the moment
 * we sent to them (STOMAIL-8310). Open and click rates are then divided by the
 * recipients we could actually measure, instead of by everyone.
 *
 * The flag is a snapshot, not a live read. Consent is a current fact about a
 * subscriber while a rate describes a past send, so reading consent at display
 * time would move every historical rate whenever somebody opted out — and,
 * because an open recorded before the opt-out stays in the numerator while its
 * recipient leaves the denominator, it could push a rate over 100%.
 *
 * DEFAULT 1 is the right answer for every row written before per-subscriber
 * opt-out shipped: those sends really were tracked.
 *
 * Cost: statistics_newsletters is the largest table in a MailPoet install.
 * Adding a column with a default is instant on MySQL 8.0.12+ (INSTANT
 * algorithm, metadata only). The index build is not instant and is what a very
 * large store will feel during the update; it is the same shape of change as
 * Migration_20260709_120000_Db, which added an index to this table in July
 * 2026. The index makes the per-newsletter untracked count an index-only scan
 * of the opted-out rows rather than a read of every recipient.
 */
class Migration_20260812_120000_Db extends DbMigration {
  public function run(): void {
    $statisticsNewslettersTable = $this->getTableName(StatisticsNewsletterEntity::class);

    if (!$this->columnExists($statisticsNewslettersTable, 'tracking_allowed')) {
      $this->connection->executeStatement("
        ALTER TABLE `{$statisticsNewslettersTable}`
        ADD COLUMN `tracking_allowed` tinyint(1) NOT NULL DEFAULT 1
      ");
    }

    if (!$this->indexExists($statisticsNewslettersTable, 'newsletter_id_tracking_allowed')) {
      $this->connection->executeStatement("
        ALTER TABLE `{$statisticsNewslettersTable}`
        ADD INDEX `newsletter_id_tracking_allowed` (`newsletter_id`, `tracking_allowed`)
      ");
    }
  }
}
