<?php

namespace MailPoet\Models;

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
}
