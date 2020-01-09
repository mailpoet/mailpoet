<?php

namespace MailPoet\Statistics\Track;

use MailPoet\Models\StatisticsClicks;
use MailPoet\Newsletter\Shortcodes\Categories\Link;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Cookies;
use MailPoet\WP\Functions as WPFunctions;

class Clicks {

  const REVENUE_TRACKING_COOKIE_NAME = 'mailpoet_revenue_tracking';
  const REVENUE_TRACKING_COOKIE_EXPIRY = 60 * 60 * 24 * 14;

  const ABANDONED_CART_COOKIE_NAME = 'mailpoet_abandoned_cart_tracking';
  const ABANDONED_CART_COOKIE_EXPIRY = 10 * 365 * 24 * 60 * 60; // 10 years (~ no expiry)

  /** @var SettingsController */
  private $settingsController;

  /** @var Cookies */
  private $cookies;

  public function __construct(SettingsController $settingsController, Cookies $cookies) {
    $this->settingsController = $settingsController;
    $this->cookies = $cookies;
  }

  /**
   * @param \stdClass|null $data
   */
  public function track($data) {
    if (!$data || empty($data->link)) {
      return $this->abort();
    }
    $subscriber = $data->subscriber;
    $queue = $data->queue;
    $newsletter = $data->newsletter;
    $link = $data->link;
    $wpUserPreview = ($data->preview && $subscriber->isWPUser());
    // log statistics only if the action did not come from
    // a WP user previewing the newsletter
    if (!$wpUserPreview) {
      $statisticsClicks = StatisticsClicks::createOrUpdateClickCount(
        $link->id,
        $subscriber->id,
        $newsletter->id,
        $queue->id
      );
      $this->sendRevenueCookie($statisticsClicks);
      $this->sendAbandonedCartCookie($subscriber);
      // track open event
      $openEvent = new Opens();
      $openEvent->track($data, $displayImage = false);
    }
    $url = $this->processUrl($link->url, $newsletter, $subscriber, $queue, $wpUserPreview);
    $this->redirectToUrl($url);
  }

  private function sendRevenueCookie(StatisticsClicks $clicks) {
    if ($this->settingsController->get('woocommerce.accept_cookie_revenue_tracking.enabled')) {
      $this->cookies->set(
        self::REVENUE_TRACKING_COOKIE_NAME,
        [
          'statistics_clicks' => $clicks->id,
          'created_at' => time(),
        ],
        [
          'expires' => time() + self::REVENUE_TRACKING_COOKIE_EXPIRY,
          'path' => '/',
        ]
      );
    }
  }

  private function sendAbandonedCartCookie($subscriber) {
    if ($this->settingsController->get('woocommerce.accept_cookie_revenue_tracking.enabled')) {
      $this->cookies->set(
        self::ABANDONED_CART_COOKIE_NAME,
        [
          'subscriber_id' => $subscriber->id,
        ],
        [
          'expires' => time() + self::ABANDONED_CART_COOKIE_EXPIRY,
          'path' => '/',
        ]
      );
    }
  }

  public function processUrl($url, $newsletter, $subscriber, $queue, $wpUserPreview) {
    if (preg_match('/\[link:(?P<action>.*?)\]/', $url, $shortcode)) {
      if (!$shortcode['action']) $this->abort();
      $url = Link::processShortcodeAction(
        $shortcode['action'],
        $newsletter,
        $subscriber,
        $queue,
        $wpUserPreview
      );
    } else {
      $shortcodes = new Shortcodes($newsletter, $subscriber, $queue, $wpUserPreview);
      $url = $shortcodes->replace($url);
    }
    return $url;
  }

  public function abort() {
    WPFunctions::get()->statusHeader(404);
    WPFunctions::get()->getTemplatePart((string)404);
    exit;
  }

  public function redirectToUrl($url) {
    header('Location: ' . $url, true, 302);
    exit;
  }
}
