<?php
namespace MailPoet\Cron\Workers;

use Carbon\Carbon;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Statistics\Track\WooCommercePurchases;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class WooCommerceOrders extends SimpleWorker {
  const TASK_TYPE = 'woocommerce_orders';
  const BATCH_SIZE = 20;

  /** @var WCHelper */
  private $woocommerce_helper;

  /** @var WooCommercePurchases */
  private $woocommerce_purchases;

  function __construct(
    WCHelper $woocommerce_helper,
    WooCommercePurchases $woocommerce_purchases,
    $timer = false
  ) {
    $this->woocommerce_helper = $woocommerce_helper;
    $this->woocommerce_purchases = $woocommerce_purchases;
    parent::__construct($timer);
  }

  function checkProcessingRequirements() {
    return $this->woocommerce_helper->isWooCommerceActive() && empty(self::getCompletedTasks()); // run only once
  }

  function processTaskStrategy(ScheduledTask $task) {
    $oldest_click = StatisticsClicks::orderByAsc('created_at')->limit(1)->findOne();
    if (!$oldest_click) {
      return true;
    }

    // continue from 'last_id' processed by previous run
    $meta = $task->getMeta();
    $last_id = isset($meta['last_id']) ? $meta['last_id'] : 0;
    add_filter('posts_where', function ($where = '') use ($last_id) {
      return $where . ' AND wp_posts.ID > ' . $last_id;
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
      $this->woocommerce_purchases->trackPurchase($order_id, false);
    }
    $task->meta = ['last_id' => end($order_ids)];
    $task->save();
    return false;
  }

  static function getNextRunDate() {
    return Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp')); // schedule immediately
  }
}
