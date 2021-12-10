<?php

namespace MailPoet\Statistics\Track;

use MailPoet\Util\Cookies;

class SubscriberCookie {
  const COOKIE_NAME = 'mailpoet_abandoned_cart_tracking';
  const COOKIE_EXPIRY = 10 * 365 * 24 * 60 * 60; // 10 years (~ no expiry)

  /** @var Cookies */
  private $cookies;

  public function __construct(
    Cookies $cookies
  ) {
    $this->cookies = $cookies;
  }

  public function getSubscriberId(): ?int {
    $data = $this->cookies->get(self::COOKIE_NAME);
    return is_array($data) && $data['subscriber_id']
      ? (int)$data['subscriber_id']
      : null;
  }

  public function setSubscriberId(int $subscriberId): void {
    $this->cookies->set(
      self::COOKIE_NAME,
      ['subscriber_id' => $subscriberId],
      [
        'expires' => time() + self::COOKIE_EXPIRY,
        'path' => '/',
      ]
    );
  }
}
