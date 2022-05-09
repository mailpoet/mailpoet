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
 * @deprecated This model is deprecated. Use \MailPoet\Statistics\StatisticsWooCommercePurchasesRepository
 * and \MailPoet\Entities\StatisticsWooCommercePurchaseEntity
 * This class can be removed after 2022-11-04.
 */
class StatisticsWooCommercePurchases extends Model {
  public static $_table = MP_STATISTICS_WOOCOMMERCE_PURCHASES_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration

  /**
   * @deprecated This is here for displaying the deprecation warning for properties.
   */
  public function __get($key) {
    self::deprecationError('property "' . $key . '"');
    return parent::__get($key);
  }

  /**
   * @deprecated This is here for displaying the deprecation warning for static calls.
   */
  public static function __callStatic($name, $arguments) {
    self::deprecationError($name);
    return parent::__callStatic($name, $arguments);
  }

  private static function deprecationError($methodName) {
    trigger_error(
      'Calling ' . esc_html($methodName) . ' is deprecated and will be removed. Use \MailPoet\Statistics\StatisticsWooCommercePurchasesRepository and \MailPoet\Entities\StatisticsWooCommercePurchaseEntity.',
      E_USER_DEPRECATED
    );
  }
}
