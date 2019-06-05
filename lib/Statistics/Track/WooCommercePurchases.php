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

    // limit clicks to 'USE_CLICKS_SINCE_DAYS_AGO' range before order has been created
    $from = clone $order->get_date_created();
    $from->modify(-self::USE_CLICKS_SINCE_DAYS_AGO . ' days');
    $to = $order->get_date_created();

    // track purchases from all clicks matched by order email
    $processed_newsletter_ids_map = [];
    $order_email_clicks = $this->getClicks($order->get_billing_email(), $from, $to);
    foreach ($order_email_clicks as $click) {
      StatisticsWooCommercePurchases::createOrUpdateByClickAndOrder($click, $order);
      $processed_newsletter_ids_map[$click->newsletter_id] = true;
    }

    if (!$use_cookies) {
      return;
    }

    // track purchases from clicks matched by cookie email (only for newsletters not tracked by order)
    $cookie_email_clicks = $this->getClicks($this->getSubscriberEmailFromCookie(), $from, $to);
    foreach ($cookie_email_clicks as $click) {
      if (isset($processed_newsletter_ids_map[$click->newsletter_id])) {
        continue; // do not track click for newsletters that were already tracked by order email
      }
      StatisticsWooCommercePurchases::createOrUpdateByClickAndOrder($click, $order);
    }
  }

  private function getClicks($email, $from, $to) {
    $subscriber = Subscriber::findOne($email);
    if (!$subscriber instanceof Subscriber) {
      return [];
    }
    return StatisticsClicks::findLatestPerNewsletterBySubscriber($subscriber, $from, $to);
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
