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

  private function processTracking(SubscriberEntity $subscriber): void {
    $this->subscribersRepository->maybeUpdateLastEngagement($subscriber);
    $this->pageViewCookie->setPageViewTimestamp($this->wp->currentTime('timestamp'));
  }

  private function shouldTrack() {
    if (!$this->trackingConfig->isCookieTrackingEnabled()) {
      return false;
    }
    $timestamp = $this->pageViewCookie->getPageViewTimestamp() ?? 0;
    return $timestamp + self::TRACK_INTERVAL < $this->wp->currentTime('timestamp');
  }

  private function getSubscriber(): ?SubscriberEntity {
    $wpUser = $this->wp->wpGetCurrentUser();
    if ($wpUser->exists()) {
      return $this->subscribersRepository->findOneBy(['wpUserId' => $wpUser->ID]);
    }

    $subscriberId = $this->subscriberCookie->getSubscriberId();
    if (!$subscriberId) {
      return null;
    }
    return $this->subscribersRepository->findOneById($subscriberId);
  }
}
