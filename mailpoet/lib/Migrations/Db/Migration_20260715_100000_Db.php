<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Migrator\DbMigration;

/**
 * Adds per-subscriber tracking consent columns (CNIL/Garante email tracking
 * consent rules). Tri-state: existing subscribers land in 'unknown' — we have
 * never asked them — which is deliberately distinct from 'granted'. The
 * `tracking.consent.track_unknown` setting (default true) preserves today's
 * behaviour for sites with no EU exposure.
 *
 * `tracking_consent_updated_at`, `_method` and `_copy` exist for compliance
 * record-keeping (when, how, and against what wording the choice was made).
 */
class Migration_20260715_100000_Db extends DbMigration {
  public function run(): void {
    $subscribersTable = $this->getTableName(SubscriberEntity::class);
    if (!$this->columnExists($subscribersTable, 'tracking_consent')) {
      $this->connection->executeStatement("
        ALTER TABLE `{$subscribersTable}`
        ADD COLUMN `tracking_consent` varchar(20) NOT NULL DEFAULT 'unknown',
        ADD COLUMN `tracking_consent_updated_at` timestamp NULL DEFAULT NULL,
        ADD COLUMN `tracking_consent_method` varchar(40) NULL DEFAULT NULL,
        ADD COLUMN `tracking_consent_copy` text NULL DEFAULT NULL
      ");
    }
  }
}
