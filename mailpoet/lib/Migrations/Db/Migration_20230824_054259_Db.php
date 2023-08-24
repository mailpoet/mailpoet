<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\StatisticsWooCommercePurchaseEntity;
use MailPoet\Migrator\DbMigration;
use MailPoet\WooCommerce\Helper;

class Migration_20230824_054259_Db extends DbMigration {

  /** @var Helper */
  private $wooCommerceHelper;

  const DEFAULT_STATUS = 'unknown';

  public function __construct(
    ContainerWrapper $container
  ) {
    parent::__construct($container);
    $this->wooCommerceHelper = $container->get(Helper::class);
  }

  public function run(): void {
    $this->createStatusColumn();
    $this->populateStatusColumn();
  }

  private function createStatusColumn(): void {
    $revenueTable = $this->getTableName(StatisticsWooCommercePurchaseEntity::class);

    if ($this->columnExists($revenueTable, 'status')) {
      return;
    }

    $this->connection->executeQuery(
      "ALTER TABLE `" . $revenueTable . "`
        ADD COLUMN `status` VARCHAR(40) NOT NULL DEFAULT '" . self::DEFAULT_STATUS . "',
        ADD INDEX `status` (`status`)"
    );
  }

  private function populateStatusColumn(): void {

    $this->wooCommerceHelper->isWooCommerceCustomOrdersTableEnabled() ?
      $this->populateStatusColumnUsingHpos() : $this->populateStatusColumnUsingPost();
  }

  private function populateStatusColumnUsingHpos(): void {
    global $wpdb;
    $revenueTable = $this->getTableName(StatisticsWooCommercePurchaseEntity::class);

    $sql = 'update ' . $revenueTable . ' as rev, ' . $wpdb->prefix . 'wc_orders as wc set rev.status=TRIM(Leading "wc-" FROM wc.status) where wc.id = rev.order_id AND rev.status="' . self::DEFAULT_STATUS . '"';
    $this->connection->executeQuery($sql);
  }

  private function populateStatusColumnUsingPost(): void {

    global $wpdb;
    $revenueTable = $this->getTableName(StatisticsWooCommercePurchaseEntity::class);

    $sql = 'update ' . $revenueTable . ' as rev, ' . $wpdb->posts . ' as wc set rev.status=TRIM(Leading "wc-" FROM wc.post_status) where wc.id = rev.order_id AND rev.status="' . self::DEFAULT_STATUS . '"';
    $this->connection->executeQuery($sql);
  }
}
