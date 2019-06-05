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

  function trackPurchase($id, $use_cookies = true) {
    $order = $this->woocommerce_helper->wcGetOrder($id);
    if (!$order instanceof WC_Order) {
      return;
    }

    // track purchases from all clicks matched by order email
    $processed_newsletter_ids_map = [];
    $order_email_clicks = $this->getClicks($order->get_billing_email(), $order->get_date_created());
    foreach ($order_email_clicks as $click) {
      StatisticsWooCommercePurchases::createOrUpdateByClickAndOrder($click, $order);
      $processed_newsletter_ids_map[$click->newsletter_id] = true;
    }

    if (!$use_cookies) {
      return;
    }

    // track purchases from clicks matched by cookie email (only for newsletters not tracked by order)
    $cookie_email_clicks = $this->getClicks($this->getSubscriberEmailFromCookie(), $order->get_date_created());
    foreach ($cookie_email_clicks as $click) {
      if (isset($processed_newsletter_ids_map[$click->newsletter_id])) {
        continue; // do not track click for newsletters that were already tracked by order email
      }
      StatisticsWooCommercePurchases::createOrUpdateByClickAndOrder($click, $order);
    }
  }

  private function getClicks($email, $before) {
    $subscriber = Subscriber::findOne($email);
    if (!$subscriber instanceof Subscriber) {
      return [];
    }

    return StatisticsClicks::findLatestPerNewsletterBySubscriber(
      $subscriber,
      $before,
      self::USE_CLICKS_SINCE_DAYS_AGO
    );
  }

  private function getSubscriberEmailFromCookie() {
    $click_cookie = $this->getClickCookie();
    if (!$click_cookie) {
      return null;
    }

    $click = StatisticsClicks::findOne($click_cookie['statistics_clicks']);
    if (!$click) {
      return null;
    }

    $subscriber = Subscriber::findOne($click->subscriber_id);
    if ($subscriber) {
      return $subscriber->email;
    }
    return null;
  }

  private function getClickCookie() {
    if (empty($_COOKIE[Clicks::REVENUE_TRACKING_COOKIE_NAME])) {
      return null;
    }
    return unserialize($_COOKIE[Clicks::REVENUE_TRACKING_COOKIE_NAME]);
  }
}
