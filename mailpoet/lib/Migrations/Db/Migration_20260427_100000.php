<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Migrator\DbMigration;

class Migration_20260427_100000 extends DbMigration {
  public function run(): void {
    $subscribersTable = $this->getTableName(SubscriberEntity::class);
    if (!$this->columnExists($subscribersTable, 'last_confirmation_email_sent_at')) {
      $this->connection->executeQuery(
        "ALTER TABLE `{$subscribersTable}`
          ADD COLUMN `last_confirmation_email_sent_at` TIMESTAMP NULL DEFAULT NULL"
      );
    }

    if (!$this->indexExists($subscribersTable, 'idx_sub_cleanup_confirmation')) {
      $this->connection->executeQuery(
        "ALTER TABLE `{$subscribersTable}`
          ADD INDEX `idx_sub_cleanup_confirmation` (
            `status`,
            `deleted_at`,
            `wp_user_id`,
            `is_woocommerce_user`,
            `last_confirmation_email_sent_at`,
            `id`
          )"
      );
    }

    if (!$this->indexExists($subscribersTable, 'idx_sub_cleanup_legacy')) {
      $this->connection->executeQuery(
        "ALTER TABLE `{$subscribersTable}`
          ADD INDEX `idx_sub_cleanup_legacy` (
            `status`,
            `deleted_at`,
            `wp_user_id`,
            `is_woocommerce_user`,
            `last_confirmation_email_sent_at`,
            `created_at`,
            `id`
          )"
      );
    }
  }
}
