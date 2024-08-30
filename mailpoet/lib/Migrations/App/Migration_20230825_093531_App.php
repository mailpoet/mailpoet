<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Doctrine\WPDB\Connection;
use MailPoet\Entities\StatisticsWooCommercePurchaseEntity;
use MailPoet\Migrations\Db\Migration_20230824_054259_Db;
use MailPoet\Migrator\AppMigration;
use MailPoet\WooCommerce\Helper;

class Migration_20230825_093531_App extends AppMigration {
  const DEFAULT_STATUS = Migration_20230824_054259_Db::DEFAULT_STATUS;

  public function run(): void {

    $wooCommerceHelper = $this->container->get(Helper::class);

    // If Woo is not active and the table doesn't exist, we can skip this migration
    if (!$wooCommerceHelper->isWooCommerceActive() && !$this->tableExists()) {
      return;
    }

    // Temporarily skip the queries in WP Playground.
    // The SQLite integration doesn't seem to support them yet.
    if (Connection::isSQLite()) {
      return;
    }

    $wooCommerceHelper->isWooCommerceCustomOrdersTableEnabled() ?
      $this->populateStatusColumnUsingHpos() : $this->populateStatusColumnUsingPost();
  }

  private function populateStatusColumnUsingHpos(): void {
    global $wpdb;

    $revenueTable = esc_sql($this->getTableName());
    $ordersTable = esc_sql($wpdb->prefix . 'wc_orders');
    $wpdb->query($wpdb->prepare(
      "UPDATE %i AS rev, %i AS wc SET rev.status=TRIM(Leading 'wc-' FROM wc.status) WHERE wc.id = rev.order_id AND rev.status= %s",
      $revenueTable,
      $ordersTable,
      self::DEFAULT_STATUS
    ));
  }

  private function populateStatusColumnUsingPost(): void {
    global $wpdb;

    $revenueTable = esc_sql($this->getTableName());
    $wpdb->query($wpdb->prepare(
      "UPDATE %i AS rev, %i AS wc SET rev.status=TRIM(Leading 'wc-' FROM wc.post_status) WHERE wc.id = rev.order_id AND rev.status= %s",
      $revenueTable,
      $wpdb->posts,
      self::DEFAULT_STATUS
    ));
  }

  private function getTableName(): string {
    return $this->entityManager->getClassMetadata(StatisticsWooCommercePurchaseEntity::class)->getTableName();
  }

  private function tableExists(): bool {
    global $wpdb;

    $revenueTable = $this->getTableName();
    return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($revenueTable))) === $revenueTable;
  }
}
