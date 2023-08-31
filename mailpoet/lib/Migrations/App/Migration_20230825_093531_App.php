<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Entities\StatisticsWooCommercePurchaseEntity;
use MailPoet\Migrations\Db\Migration_20230824_054259_Db;
use MailPoet\Migrator\AppMigration;
use MailPoet\WooCommerce\Helper;

class Migration_20230825_093531_App extends AppMigration {
  const DEFAULT_STATUS = Migration_20230824_054259_Db::DEFAULT_STATUS;

  public function run(): void {

    $wooCommerceHelper = $this->container->get(Helper::class);
    $wooCommerceHelper->isWooCommerceCustomOrdersTableEnabled() ?
      $this->populateStatusColumnUsingHpos() : $this->populateStatusColumnUsingPost();
  }

  private function populateStatusColumnUsingHpos(): void {
    global $wpdb;

    $revenueTable = esc_sql($this->getTableName());
    $sql = "update " . $revenueTable . " as rev, " . $wpdb->prefix . "wc_orders as wc set rev.status=TRIM(Leading 'wc-' FROM wc.status) where wc.id = rev.order_id AND rev.status='" . self::DEFAULT_STATUS . "'";
    $wpdb->query($sql);
  }

  private function populateStatusColumnUsingPost(): void {
    global $wpdb;

    $revenueTable = esc_sql($this->getTableName());
    $sql = "update " . $revenueTable . " as rev, " . $wpdb->posts . " as wc set rev.status=TRIM(Leading 'wc-' FROM wc.post_status) where wc.id = rev.order_id AND rev.status='" . self::DEFAULT_STATUS . "'";
    $wpdb->query($sql);
  }

  private function getTableName(): string {
    return $this->entityManager->getClassMetadata(StatisticsWooCommercePurchaseEntity::class)->getTableName();
  }
}
