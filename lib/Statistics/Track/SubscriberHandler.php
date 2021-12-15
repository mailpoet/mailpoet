<?php declare(strict_types = 1);

namespace MailPoet\Statistics\Track;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Subscribers\SubscribersRepository;

class SubscriberHandler {
  /** @var SubscriberCookie */
  private $subscriberCookie;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var TrackingConfig */
  private $trackingConfig;

  public function __construct(
    SubscriberCookie $subscriberCookie,
    SubscribersRepository $subscribersRepository,
    TrackingConfig $trackingConfig
  ) {
    $this->subscriberCookie = $subscriberCookie;
    $this->subscribersRepository = $subscribersRepository;
    $this->trackingConfig = $trackingConfig;
  }

  public function identifyByEmail(string $email): void {
    if (!$this->trackingConfig->isCookieTrackingEnabled()) {
      return;
    }

    $subscriber = $this->subscribersRepository->findOneBy(['email' => $email]);
    if ($subscriber) {
      $this->setCookieBySubscriber($subscriber);
    }
  }

  private function setCookieBySubscriber(SubscriberEntity $subscriber): void {
    $subscriberId = $subscriber->getId();
    if ($subscriberId) {
      $this->subscriberCookie->setSubscriberId($subscriberId);
    }
  }
}
