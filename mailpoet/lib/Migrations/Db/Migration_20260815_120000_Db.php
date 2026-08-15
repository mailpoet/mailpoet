<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Migrator\DbMigration;

/**
 * Index on subscribers.tracking_consent (STOMAIL-8310).
 *
 * Tracked-only open and click rates count, per campaign, the recipients who
 * were 'denied' before the send. On most sites denied is rare, and this index
 * is what lets MySQL start from the few denied subscribers and look up their
 * sent rows through the existing statistics_newsletters.subscriber_id key,
 * instead of reading every recipient of every campaign on the listing page.
 *
 * Not needed for correctness: the query returns the same answer without it,
 * so the read path needs no schema guard while a site is mid-update.
 */
//phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20260815_120000_Db extends DbMigration {
  public function run(): void {
    $subscribersTable = $this->getTableName(SubscriberEntity::class);

    if (!$this->indexExists($subscribersTable, 'tracking_consent')) {
      $this->connection->executeStatement("
        ALTER TABLE `{$subscribersTable}`
        ADD INDEX `tracking_consent` (`tracking_consent`)
      ");
    }
  }
}
