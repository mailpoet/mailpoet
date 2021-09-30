<?php

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\StatisticsWooCommercePurchases;
use MailPoet\Statistics\Track\WooCommercePurchases;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoetVendor\Carbon\Carbon;

class WooCommercePastOrders extends SimpleWorker {
  const TASK_TYPE = 'woocommerce_past_orders';
  const BATCH_SIZE = 20;

  /** @var WCHelper */
  private $woocommerceHelper;

  /** @var WooCommercePurchases */
  private $woocommercePurchases;

  public function __construct(
    WCHelper $woocommerceHelper,
    WooCommercePurchases $woocommercePurchases
  ) {
    $this->woocommerceHelper = $woocommerceHelper;
    $this->woocommercePurchases = $woocommercePurchases;
    parent::__construct();
  }

  public function checkProcessingRequirements() {
    return $this->woocommerceHelper->isWooCommerceActive() && empty($this->getCompletedTasks()); // run only once
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    $oldestClick = StatisticsClicks::orderByAsc('created_at')->limit(1)->findOne();
    if (!$oldestClick instanceof StatisticsClicks) {
      return true;
    }

    // continue from 'last_processed_id' from previous run
    $meta = $task->getMeta();
    $lastId = isset($meta['last_processed_id']) ? $meta['last_processed_id'] : 0;
    add_filter('posts_where', function ($where = '') use ($lastId) {
      global $wpdb;
      return $where . " AND {$wpdb->prefix}posts.ID > " . $lastId;
    }, 10, 2);

    $orderIds = $this->woocommerceHelper->wcGetOrders([
      'status' => 'completed',
      'date_completed' => '>=' . $oldestClick->createdAt,
      'orderby' => 'ID',
      'order' => 'ASC',
      'limit' => self::BATCH_SIZE,
      'return' => 'ids',
    ]);

    if (empty($orderIds)) {
      return true;
    }

    foreach ($orderIds as $orderId) {
      // clean all records for given order to fix wrong data inserted by a past buggy version
      StatisticsWooCommercePurchases::where('order_id', $orderId)->deleteMany();
      $this->woocommercePurchases->trackPurchase($orderId, false);
    }

    $task->setMeta(['last_processed_id' => end($orderIds)]);
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();

    return false;
  }

  public function getNextRunDate() {
    return Carbon::createFromTimestamp($this->wp->currentTime('timestamp')); // schedule immediately
  }
}
