<?php

namespace MailPoet\Models;

use WC_Order;

/**
 * @property int $newsletterId
 * @property int $subscriberId
 * @property int $queueId
 * @property int $clickId
 * @property int $orderId
 * @property string $orderCurrency
 * @property float $orderPriceTotal
 */
class StatisticsWooCommercePurchases extends Model {
  public static $_table = MP_STATISTICS_WOOCOMMERCE_PURCHASES_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration

  public static function createOrUpdateByClickDataAndOrder(StatisticsClicks $click, WC_Order $order) {
    // search by subscriber and newsletter IDs (instead of click itself) to avoid duplicities
    // when a new click from the subscriber appeared since last tracking for given newsletter
    // (this will keep the originally tracked click - likely the click that led to the order)
    $statistics = self::where('order_id', $order->get_id())
      ->where('subscriber_id', $click->subscriberId)
      ->where('newsletter_id', $click->newsletterId)
      ->findOne();

    if (!$statistics instanceof self) {
      $statistics = self::create();
      $statistics->newsletterId = $click->newsletterId;
      $statistics->subscriberId = $click->subscriberId;
      $statistics->queueId = $click->queueId;
      $statistics->clickId = (int)$click->id;
      $statistics->orderId = $order->get_id();
    }

    $statistics->orderCurrency = $order->get_currency();
    $statistics->orderPriceTotal = (float)$order->get_total();
    return $statistics->save();
  }
}
