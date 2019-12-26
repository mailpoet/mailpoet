<?php

namespace MailPoet\Models;

use WC_Order;

/**
 * @property int $newsletter_id
 * @property int $subscriber_id
 * @property int $queue_id
 * @property int $click_id
 * @property int $order_id
 * @property string $order_currency
 * @property float $order_price_total
 */
class StatisticsWooCommercePurchases extends Model {
  public static $_table = MP_STATISTICS_WOOCOMMERCE_PURCHASES_TABLE;

  public static function createOrUpdateByClickDataAndOrder(StatisticsClicks $click, WC_Order $order) {
    // search by subscriber and newsletter IDs (instead of click itself) to avoid duplicities
    // when a new click from the subscriber appeared since last tracking for given newsletter
    // (this will keep the originally tracked click - likely the click that led to the order)
    $statistics = self::where('order_id', $order->get_id())
      ->where('subscriber_id', $click->subscriber_id)
      ->where('newsletter_id', $click->newsletter_id)
      ->findOne();

    if (!$statistics instanceof self) {
      $statistics = self::create();
      $statistics->newsletter_id = $click->newsletter_id;
      $statistics->subscriber_id = $click->subscriber_id;
      $statistics->queue_id = $click->queue_id;
      $statistics->click_id = (int)$click->id;
      $statistics->order_id = $order->get_id();
    }

    $statistics->order_currency = $order->get_currency();
    $statistics->order_price_total = (float)$order->get_total();
    return $statistics->save();
  }
}
