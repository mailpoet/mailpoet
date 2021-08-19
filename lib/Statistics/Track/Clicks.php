<?php

namespace MailPoet\Statistics\Track;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\UserAgentEntity;
use MailPoet\Newsletter\Shortcodes\Categories\Link as LinkShortcodeCategory;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\StatisticsClicksRepository;
use MailPoet\Statistics\UserAgentsRepository;
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

  /** @var LinkShortcodeCategory */
  private $linkShortcodeCategory;

  /** @var Opens */
  private $opens;

  /** @var StatisticsClicksRepository */
  private $statisticsClicksRepository;

  /** @var UserAgentsRepository */
  private $userAgentsRepository;

  public function __construct(
    SettingsController $settingsController,
    Cookies $cookies,
    Shortcodes $shortcodes,
    Opens $opens,
    StatisticsClicksRepository $statisticsClicksRepository,
    UserAgentsRepository $userAgentsRepository,
    LinkShortcodeCategory $linkShortcodeCategory
  ) {
    $this->settingsController = $settingsController;
    $this->cookies = $cookies;
    $this->shortcodes = $shortcodes;
    $this->linkShortcodeCategory = $linkShortcodeCategory;
    $this->opens = $opens;
    $this->statisticsClicksRepository = $statisticsClicksRepository;
    $this->userAgentsRepository = $userAgentsRepository;
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
    $wpUserPreview = ($data->preview && ($subscriber->isWPUser()));
    // log statistics only if the action did not come from
    // a WP user previewing the newsletter
    if (!$wpUserPreview) {
      $statisticsClicks = $this->statisticsClicksRepository->createOrUpdateClickCount(
        $link,
        $subscriber,
        $newsletter,
        $queue
      );
      if (!empty($data->userAgent)) {
        $userAgent = $this->userAgentsRepository->findOrCreate($data->userAgent);
        if ($userAgent->getUserAgentType() === UserAgentEntity::USER_AGENT_TYPE_HUMAN
          || $statisticsClicks->getUserAgentType() !== UserAgentEntity::USER_AGENT_TYPE_HUMAN
        ) {
          $statisticsClicks->setUserAgent($userAgent);
          $statisticsClicks->setUserAgentType($userAgent->getUserAgentType());
        }
      }
      $this->statisticsClicksRepository->flush();
      $this->sendRevenueCookie($statisticsClicks);
      $this->sendAbandonedCartCookie($subscriber);
      // track open event
      $this->opens->track($data, $displayImage = false);
    }
    $url = $this->processUrl($link->getUrl(), $newsletter, $subscriber, $queue, $wpUserPreview);
    $this->redirectToUrl($url);
  }

  private function sendRevenueCookie(StatisticsClickEntity $clicks) {
    if ($this->settingsController->get('woocommerce.accept_cookie_revenue_tracking.enabled')) {
      $this->cookies->set(
        self::REVENUE_TRACKING_COOKIE_NAME,
        [
          'statistics_clicks' => $clicks->getId(),
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
          'subscriber_id' => $subscriber->getId(),
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
      $url = $this->linkShortcodeCategory->processShortcodeAction(
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
    global $wp_query;// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    WPFunctions::get()->statusHeader(404);
    $wp_query->set_404();// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    WPFunctions::get()->getTemplatePart((string)404);
    exit;
  }

  public function redirectToUrl($url) {
    header('Location: ' . $url, true, 302);
    exit;
  }
}
