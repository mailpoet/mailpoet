<?php

namespace MailPoet\Statistics\Track;

use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\StatisticsWooCommercePurchases;
use MailPoet\Models\Subscriber;
use MailPoet\Util\Cookies;
use MailPoet\WooCommerce\Helper;
use WC_Order;

class WooCommercePurchases {
  const USE_CLICKS_SINCE_DAYS_AGO = 14;

  /** @var Helper */
  private $woocommerceHelper;

  /** @var Cookies */
  private $cookies;

  public function __construct(
    Helper $woocommerceHelper,
    Cookies $cookies
  ) {
    $this->woocommerceHelper = $woocommerceHelper;
    $this->cookies = $cookies;
  }

  public function trackPurchase($id, $useCookies = true) {
    $order = $this->woocommerceHelper->wcGetOrder($id);
    if (!$order instanceof WC_Order) {
      return;
    }

    // limit clicks to 'USE_CLICKS_SINCE_DAYS_AGO' range before order has been created
    $fromDate = $order->get_date_created();
    if (is_null($fromDate)) {
      return;
    }
    $from = clone $fromDate;
    $from->modify(-self::USE_CLICKS_SINCE_DAYS_AGO . ' days');
    $to = $order->get_date_created();

    // track purchases from all clicks matched by order email
    $processedNewsletterIdsMap = [];
    $orderEmailClicks = $this->getClicks($order->get_billing_email(), $from, $to);
    foreach ($orderEmailClicks as $click) {
      StatisticsWooCommercePurchases::createOrUpdateByClickDataAndOrder($click, $order);
      $processedNewsletterIdsMap[$click->newsletterId] = true;
    }

    if (!$useCookies) {
      return;
    }

    // track purchases from clicks matched by cookie email (only for newsletters not tracked by order)
    $cookieEmailClicks = $this->getClicks($this->getSubscriberEmailFromCookie(), $from, $to);
    foreach ($cookieEmailClicks as $click) {
      if (isset($processedNewsletterIdsMap[$click->newsletterId])) {
        continue; // do not track click for newsletters that were already tracked by order email
      }
      StatisticsWooCommercePurchases::createOrUpdateByClickDataAndOrder($click, $order);
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
    $cookieData = $this->cookies->get(Clicks::REVENUE_TRACKING_COOKIE_NAME);
    if (!$cookieData) {
      return null;
    }

    $click = StatisticsClicks::findOne($cookieData['statistics_clicks']);
    if (!$click instanceof StatisticsClicks) {
      return null;
    }

    $subscriber = Subscriber::findOne($click->subscriberId);
    if ($subscriber) {
      return $subscriber->email;
    }
    return null;
  }
}
