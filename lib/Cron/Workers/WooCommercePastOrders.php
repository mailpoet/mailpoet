<?php

namespace MailPoet\Cron\Workers;

use MailPoet\Models\ScheduledTask;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\StatisticsWooCommercePurchases;
use MailPoet\Statistics\Track\WooCommercePurchases;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class WooCommercePastOrders extends SimpleWorker {
  const TASK_TYPE = 'woocommerce_past_orders';
  const BATCH_SIZE = 20;

  /** @var WCHelper */
  private $woocommerce_helper;

  /** @var WooCommercePurchases */
  private $woocommerce_purchases;

  function __construct(
    WCHelper $woocommerce_helper,
    WooCommercePurchases $woocommerce_purchases
  ) {
    $this->woocommerce_helper = $woocommerce_helper;
    $this->woocommerce_purchases = $woocommerce_purchases;
    parent::__construct();
  }

  function checkProcessingRequirements() {
    return $this->woocommerce_helper->isWooCommerceActive() && empty($this->getCompletedTasks()); // run only once
  }

  function processTaskStrategy(ScheduledTask $task, $timer) {
    $oldest_click = StatisticsClicks::orderByAsc('created_at')->limit(1)->findOne();
    if (!$oldest_click instanceof StatisticsClicks) {
      return true;
    }

    // continue from 'last_processed_id' from previous run
    $meta = $task->getMeta();
    $last_id = isset($meta['last_processed_id']) ? $meta['last_processed_id'] : 0;
    add_filter('posts_where', function ($where = '') use ($last_id) {
      global $wpdb;
      return $where . " AND {$wpdb->prefix}posts.ID > " . $last_id;
    }, 10, 2);

    $order_ids = $this->woocommerce_helper->wcGetOrders([
      'status' => 'completed',
      'date_completed' => '>=' . $oldest_click->created_at,
      'orderby' => 'ID',
      'order' => 'ASC',
      'limit' => self::BATCH_SIZE,
      'return' => 'ids',
    ]);

    if (empty($order_ids)) {
      return true;
    }

    foreach ($order_ids as $order_id) {
      // clean all records for given order to fix wrong data inserted by a past buggy version
      StatisticsWooCommercePurchases::where('order_id', $order_id)->deleteMany();
      $this->woocommerce_purchases->trackPurchase($order_id, false);
    }
    $task->meta = ['last_processed_id' => end($order_ids)];
    $task->save();
    return false;
  }

  function getNextRunDate() {
    return Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp')); // schedule immediately
  }
}
