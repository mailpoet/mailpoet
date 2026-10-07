<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use MailPoet\WP\Functions as WPFunctions;

class FormRenderStamp {
  const FIELD_NAME = 'form_stamp';
  const MAX_FUTURE_SKEW = 60;

  private WPFunctions $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function issue(): string {
    $timestamp = time();
    return $timestamp . '.' . $this->sign($timestamp);
  }

  /**
   * Seconds since the stamp was issued, or null when it is malformed, forged
   * or issued in the future.
   *
   * @param mixed $stamp
   */
  public function elapsedSeconds($stamp): ?int {
    if (!is_string($stamp)) {
      return null;
    }
    $parts = explode('.', $stamp);
    if (count($parts) !== 2 || $parts[0] === '' || strlen($parts[0]) > 12 || !ctype_digit($parts[0])) {
      return null;
    }
    $timestamp = (int)$parts[0];
    if (!hash_equals($this->sign($timestamp), $parts[1])) {
      return null;
    }
    $now = time();
    if ($timestamp > $now + self::MAX_FUTURE_SKEW) {
      return null;
    }
    return max(0, $now - $timestamp);
  }

  private function sign(int $timestamp): string {
    return hash_hmac('sha256', (string)$timestamp, (string)$this->wp->wpSalt('nonce'));
  }
}
