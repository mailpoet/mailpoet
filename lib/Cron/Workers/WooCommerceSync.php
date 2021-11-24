<?php

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Segments\WooCommerce as WooCommerceSegment;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoetVendor\Doctrine\DBAL\Connection;

class WooCommerceSync extends SimpleWorker {
  const TASK_TYPE = 'woocommerce_sync';
  const SUPPORT_MULTIPLE_INSTANCES = false;
  const AUTOMATIC_SCHEDULING = false;

  /** @var WooCommerceSegment */
  private $woocommerceSegment;

  /** @var WooCommerceHelper */
  private $woocommerceHelper;

  /** @var Connection */
  private $connection;

  public function __construct(
    WooCommerceSegment $woocommerceSegment,
    WooCommerceHelper $woocommerceHelper,
    Connection $connection
  ) {
    $this->woocommerceSegment = $woocommerceSegment;
    $this->woocommerceHelper = $woocommerceHelper;
    $this->connection = $connection;
    parent::__construct();
  }

  public function checkProcessingRequirements() {
    return $this->woocommerceHelper->isWooCommerceActive();
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    $lastProcessedOrderId = $task->getMeta()['last_processed_order_id'] ?? 0;
    $highestOrderId = $this->getHighestOrderId();

    $lastProcessedOrderId = $this->woocommerceSegment->synchronizeCustomers($lastProcessedOrderId, $highestOrderId);

    $meta = $task->getMeta() ?? [];
    $meta['last_processed_order_id'] = $lastProcessedOrderId;
    $task->setMeta($meta);
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();

    if ($lastProcessedOrderId !== $highestOrderId) {
      return false;
    }
    return true;
  }

  private function getHighestOrderId(): int {
    global $wpdb;
    return (int)$this->connection->fetchOne("
      SELECT MAX(wpp.ID)
      FROM {$wpdb->posts} wpp
      WHERE wpp.post_type = 'shop_order'
    ");
  }
}
