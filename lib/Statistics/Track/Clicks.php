<?php

namespace MailPoet\Statistics\Track;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
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

  /** @var Shortcodes */
  private $shortcodes;

  public function __construct(
    SettingsController $settingsController,
    Cookies $cookies,
    Shortcodes $shortcodes
  ) {
    $this->settingsController = $settingsController;
    $this->cookies = $cookies;
    $this->shortcodes = $shortcodes;
  }

  /**
   * @param \stdClass|null $data
   */
  public function track($data) {
    if (!$data || empty($data->link)) {
      return $this->abort();
    }
    /** @var SubscriberEntity $subscriber */
    $subscriber = $data->subscriber;
    /** @var SendingQueueEntity $queue */
    $queue = $data->queue;
    /** @var NewsletterEntity $newsletter */
    $newsletter = $data->newsletter;
    /** @var NewsletterLinkEntity $link */
    $link = $data->link;
    $wpUserPreview = ($data->preview && ($subscriber->getWpUserId() > 0));
    // log statistics only if the action did not come from
    // a WP user previewing the newsletter
    if (!$wpUserPreview) {
      $statisticsClicks = StatisticsClicks::createOrUpdateClickCount(
        $link->getId(),
        $subscriber->getId(),
        $newsletter->getId(),
        $queue->getId()
      );
      $this->sendRevenueCookie($statisticsClicks);
      $this->sendAbandonedCartCookie($subscriber);
      // track open event
      $openEvent = new Opens();
      $openEvent->track($data, $displayImage = false);
    }
    $url = $this->processUrl($link->getUrl(), $newsletter, $subscriber, $queue, $wpUserPreview);
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

  public function processUrl(
    string $url,
    NewsletterEntity $newsletter,
    SubscriberEntity $subscriber,
    SendingQueueEntity $queue,
    bool $wpUserPreview
  ) {
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
      $this->shortcodes->setQueue($queue);
      $this->shortcodes->setNewsletter($newsletter);
      $this->shortcodes->setSubscriber($subscriber);
      $this->shortcodes->setWpUserPreview($wpUserPreview);
      $url = $this->shortcodes->replace($url);
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
