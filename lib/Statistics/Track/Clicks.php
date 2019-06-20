<?php
namespace MailPoet\Statistics\Track;

use MailPoet\Models\StatisticsClicks;
use MailPoet\Newsletter\Shortcodes\Categories\Link;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Cookies;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class Clicks {

  const REVENUE_TRACKING_COOKIE_NAME = 'mailpoet_revenue_tracking';
  const REVENUE_TRACKING_COOKIE_EXPIRY = 60 * 60 * 24 * 14;

  const ABANDONED_CART_COOKIE_NAME = 'mailpoet_abandoned_cart_tracking';
  const ABANDONED_CART_COOKIE_EXPIRY = 10 * 365 * 24 * 60 * 60; // 10 years (~ no expiry)

  /** @var SettingsController */
  private $settings_controller;

  /** @var Cookies */
  private $cookies;

  public function __construct(SettingsController $settings_controller, Cookies $cookies) {
    $this->settings_controller = $settings_controller;
    $this->cookies = $cookies;
  }

  /**
   * @param \stdClass|null $data
   */
  function track($data) {
    if (!$data || empty($data->link)) {
      return $this->abort();
    }
    $subscriber = $data->subscriber;
    $queue = $data->queue;
    $newsletter = $data->newsletter;
    $link = $data->link;
    $wp_user_preview = ($data->preview && $subscriber->isWPUser());
    // log statistics only if the action did not come from
    // a WP user previewing the newsletter
    if (!$wp_user_preview) {
      $statistics_clicks = StatisticsClicks::createOrUpdateClickCount(
        $link->id,
        $subscriber->id,
        $newsletter->id,
        $queue->id
      );
      $this->sendRevenueCookie($statistics_clicks);
      $this->sendAbandonedCartCookie($subscriber);
      // track open event
      $open_event = new Opens();
      $open_event->track($data, $display_image = false);
    }
    $url = $this->processUrl($link->url, $newsletter, $subscriber, $queue, $wp_user_preview);
    $this->redirectToUrl($url);
  }

  private function sendRevenueCookie(StatisticsClicks $clicks) {
    if ($this->settings_controller->get('woocommerce.accept_cookie_revenue_tracking.enabled')) {
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
    if ($this->settings_controller->get('woocommerce.accept_cookie_revenue_tracking.enabled')) {
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

  function processUrl($url, $newsletter, $subscriber, $queue, $wp_user_preview) {
    if (preg_match('/\[link:(?P<action>.*?)\]/', $url, $shortcode)) {
      if (!$shortcode['action']) $this->abort();
      $url = Link::processShortcodeAction(
        $shortcode['action'],
        $newsletter,
        $subscriber,
        $queue,
        $wp_user_preview
      );
    } else {
      $shortcodes = new Shortcodes($newsletter, $subscriber, $queue, $wp_user_preview);
      $url = $shortcodes->replace($url);
    }
    return $url;
  }

  function abort() {
    WPFunctions::get()->statusHeader(404);
    WPFunctions::get()->getTemplatePart((string)404);
    exit;
  }

  function redirectToUrl($url) {
    header('Location: ' . $url, true, 302);
    exit;
  }
}
