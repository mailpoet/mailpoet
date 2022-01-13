<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Segments\WooCommerce as WooCommerceSegment;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoetVendor\Doctrine\DBAL\Connection;

class WooCommerceSync extends SimpleWorker {
  const TASK_TYPE = 'woocommerce_sync';
  const SUPPORT_MULTIPLE_INSTANCES = false;
  const AUTOMATIC_SCHEDULING = false;
  const BATCH_SIZE = 1000;

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
    $meta = $task->getMeta();
    $highestOrderId = $this->getHighestOrderId();

    if (!isset($meta['last_checked_order_id'])) {
      $meta['last_checked_order_id'] = 0;
    }

    do {
      $this->cronHelper->enforceExecutionLimit($timer);
      $meta['last_checked_order_id'] = $this->woocommerceSegment->synchronizeCustomers(
        $meta['last_checked_order_id'],
        $highestOrderId,
        self::BATCH_SIZE
      );
      $task->setMeta($meta);
      $this->scheduledTasksRepository->persist($task);
      $this->scheduledTasksRepository->flush();
    } while ($meta['last_checked_order_id'] < $highestOrderId);

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
