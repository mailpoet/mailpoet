<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Statistics\Track;

use MailPoet\Settings\TrackingConfig;
use MailPoet\Util\Cookies;
use MailPoet\WP\Functions as WPFunctions;

class SubscriberCookie {
  const COOKIE_NAME = 'mailpoet_subscriber';
  const COOKIE_NAME_LEGACY = 'mailpoet_abandoned_cart_tracking';
  const COOKIE_EXPIRY = 10 * 365 * 24 * 60 * 60; // 10 years (~ no expiry)

  /** @var Cookies */
  private $cookies;

  /** @var TrackingConfig */
  private $trackingConfig;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    Cookies $cookies,
    TrackingConfig $trackingConfig,
    WPFunctions $wp
  ) {
    $this->cookies = $cookies;
    $this->trackingConfig = $trackingConfig;
    $this->wp = $wp;
  }

  public function getSubscriberId(): ?int {
    if (!$this->trackingConfig->isCookieTrackingEnabled()) {
      return null;
    }

    if ($this->cookies->get(self::COOKIE_NAME_LEGACY) !== null) {
      $this->cookies->delete(self::COOKIE_NAME_LEGACY);
    }

    return $this->getSubscriberIdFromCookie();
  }

  public function setSubscriberId(int $subscriberId): void {
    if (!$this->trackingConfig->isCookieTrackingEnabled()) {
      return;
    }

    $this->cookies->set(
      self::COOKIE_NAME,
      $this->getCookieData($subscriberId),
      [
        'expires' => time() + self::COOKIE_EXPIRY,
        'path' => '/',
      ]
    );
  }

  public function getCookieData(int $subscriberId): array {
    return [
      'subscriber_id' => $subscriberId,
      'hash' => $this->getHash($subscriberId),
    ];
  }

  private function getSubscriberIdFromCookie(): ?int {
    $data = $this->cookies->get(self::COOKIE_NAME);
    if (
      !is_array($data)
      || !isset($data['subscriber_id'], $data['hash'])
      || !is_numeric($data['subscriber_id'])
      || !is_string($data['hash'])
    ) {
      return null;
    }
    $subscriberId = (int)$data['subscriber_id'];
    return hash_equals($this->getHash($subscriberId), $data['hash']) ? $subscriberId : null;
  }

  private function getHash(int $subscriberId): string {
    $data = self::COOKIE_NAME . '|' . $this->wp->getCurrentBlogId() . '|' . $subscriberId;
    return hash_hmac('sha256', $data, (string)$this->wp->wpSalt('auth'));
  }
}
