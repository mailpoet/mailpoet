<?php declare(strict_types=1);

namespace MailPoet\Statistics\Track;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WP\Functions as WPFunctions;

class SubscriberActivityTracker {

  const TRACK_INTERVAL = 60; // 1 minute

  /** @var PageViewCookie */
  private $pageViewCookie;

  /** @var SubscriberCookie */
  private $subscriberCookie;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var  WPFunctions */
  private $wp;

  /** @var TrackingConfig */
  private $trackingConfig;

  /** @var ?SubscriberEntity */
  private $activeSubscriber;

  /** @var callable[] */
  private $callbacks = [];

  public function __construct(
    PageViewCookie $pageViewCookie,
    SubscriberCookie $subscriberCookie,
    SubscribersRepository $subscribersRepository,
    WPFunctions $wp,
    TrackingConfig $trackingConfig
  ) {
    $this->pageViewCookie = $pageViewCookie;
    $this->subscriberCookie = $subscriberCookie;
    $this->subscribersRepository = $subscribersRepository;
    $this->wp = $wp;
    $this->trackingConfig = $trackingConfig;
  }

  public function trackActivity(): bool {
    if (!$this->shouldTrack()) {
      return false;
    }
    $subscriber = $this->getSubscriber();
    if (!$subscriber) {
      return false;
    }
    $this->processTracking($subscriber);
    return true;
  }

  public function registerCallback(string $slug, callable $callback) {
    $this->callbacks[$slug] = $callback;
  }

  public function unregisterCallback(string $slug) {
    unset($this->callbacks[$slug]);
  }

  private function processTracking(SubscriberEntity $subscriber): void {
    $this->subscribersRepository->maybeUpdateLastEngagement($subscriber);
    $this->pageViewCookie->setPageViewTimestamp($this->wp->currentTime('timestamp'));
    foreach ($this->callbacks as $callback) {
      $callback($subscriber);
    }
  }

  private function shouldTrack() {
    // Don't track in admin interface
    if ($this->wp->isAdmin()) {
      return false;
    }

    $timestamp = $this->getLatestTimestamp();
    // Cookie tracking is disabled and there is no logged-in subscriber who could be used to determine last activity timestamp
    if ($timestamp === null) {
      return false;
    }
    return $timestamp + self::TRACK_INTERVAL < $this->wp->currentTime('timestamp');
  }

  private function getLatestTimestamp(): ?int {
    if ($this->trackingConfig->isCookieTrackingEnabled()) {
      return $this->pageViewCookie->getPageViewTimestamp() ?? 0;
    }
    // In case the cookie tracking is disabled fallback to last engagement of currently logged subscriber
    $subscriber = $this->getSubscriber();
    if (!$subscriber) {
      return null;
    }
    return $subscriber->getLastEngagementAt() ? $subscriber->getLastEngagementAt()->getTimestamp() : 0;
  }

  private function getSubscriber(): ?SubscriberEntity {
    if ($this->activeSubscriber instanceof SubscriberEntity) {
      return $this->activeSubscriber;
    }
    $wpUser = $this->wp->wpGetCurrentUser();
    if ($wpUser->exists()) {
      $this->activeSubscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $wpUser->ID]);
      return $this->activeSubscriber;
    }

    $subscriberId = $this->subscriberCookie->getSubscriberId();
    if (!$subscriberId) {
      return null;
    }
    $this->activeSubscriber = $this->subscribersRepository->findOneById($subscriberId);
    return $this->activeSubscriber;
  }
}
