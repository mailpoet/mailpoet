<?php
namespace MailPoet\Models;

use WC_Order;

if (!defined('ABSPATH')) exit;

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

  static function createOrUpdateByClickAndOrder(StatisticsClicks $click, WC_Order $order) {
    $statistics = self::where('click_id', $click->id)
      ->where('order_id', $order->get_id())
      ->findOne();

    if (!$statistics) {
      $statistics = self::create();
      $statistics->newsletter_id = $click->newsletter_id;
      $statistics->subscriber_id = $click->subscriber_id;
      $statistics->queue_id = $click->queue_id;
      $statistics->click_id = $click->id;
      $statistics->order_id = $order->get_id();
    }

    $statistics->order_currency = $order->get_currency();
    $statistics->order_price_total = (float)$order->get_total();
    return $statistics->save();
  }
}
