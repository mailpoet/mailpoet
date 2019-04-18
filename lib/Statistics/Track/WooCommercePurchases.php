<?php
namespace MailPoet\Statistics\Track;

use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\StatisticsWooCommercePurchases;
use MailPoet\Models\Subscriber;
use MailPoet\WooCommerce\Helper;
use WC_Order;

if (!defined('ABSPATH')) exit;

class WooCommercePurchases {
  const USE_CLICKS_SINCE_DAYS_AGO = 14;

  /** @var Helper */
  private $woocommerce_helper;

  function __construct(Helper $woocommerce_helper) {
    $this->woocommerce_helper = $woocommerce_helper;
  }

  function trackPurchase($id) {
    $order = $this->woocommerce_helper->wcGetOrder($id);
    if (!$order instanceof WC_Order) {
      return;
    }

    $subscriber = Subscriber::findOne($order->get_billing_email());
    if (!$subscriber instanceof Subscriber) {
      return;
    }

    $clicks = StatisticsClicks::findLatestPerNewsletterBySubscriber(
      $subscriber,
      $order->get_date_created(),
      self::USE_CLICKS_SINCE_DAYS_AGO
    );

    foreach ($clicks as $click) {
      StatisticsWooCommercePurchases::createOrUpdateByClickAndOrder($click, $order);
    }
  }
}
