<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use MailPoet\Logging\LoggerFactory;
use MailPoet\Util\Helpers;
use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;

class CaptchaSession {
  const EXPIRATION = 1800; // 30 minutes
  const ID_LENGTH = 32;
  const NEW_SESSION_LIMIT = 200;
  const NEW_SESSION_WINDOW = 600; // 10 minutes

  /** Keys used only by the registration CAPTCHA page. */
  const REGISTER_ONLY_KEYS = ['referrer_form', 'referrer_form_url', 'rendered', 'action_url'];

  const SESSION_HASH_KEY = 'hash';
  const SESSION_FORM_KEY = 'form';

  private WPFunctions $wp;

  private LoggerFactory $loggerFactory;

  public function __construct(
    WPFunctions $wp,
    ?LoggerFactory $loggerFactory = null
  ) {
    $this->wp = $wp;
    $this->loggerFactory = $loggerFactory ?? LoggerFactory::getInstance();
  }

  public function generateSessionId(): string {
    return Security::generateRandomString(self::ID_LENGTH);
  }

  /**
   * @param mixed $sessionId
   */
  public function isValidId($sessionId): bool {
    return is_string($sessionId) && strlen($sessionId) === self::ID_LENGTH && ctype_alnum($sessionId);
  }

  public function exists(string $sessionId): bool {
    if (!$this->isValidId($sessionId)) {
      return false;
    }
    return $this->wp->getTransient($this->getKey($sessionId, self::SESSION_FORM_KEY)) !== false
      || $this->wp->getTransient($this->getKey($sessionId, self::SESSION_HASH_KEY)) !== false;
  }

  public function reset(string $sessionId): void {
    if (!$this->isValidId($sessionId)) {
      return;
    }
    $formKey = $this->getKey($sessionId, self::SESSION_FORM_KEY);
    $this->wp->deleteTransient($formKey);
    $this->deleteCaptchaHash($sessionId);
  }

  /**
   * Returns true only when this call removed the hash, so concurrent requests cannot both claim it.
   */
  public function deleteCaptchaHash(string $sessionId): bool {
    if (!$this->isValidId($sessionId)) {
      return false;
    }
    return (bool)$this->wp->deleteTransient($this->getKey($sessionId, self::SESSION_HASH_KEY));
  }

  public function setFormData(string $sessionId, array $data): void {
    if (!$this->isValidId($sessionId)) {
      return;
    }
    $key = $this->getKey($sessionId, self::SESSION_FORM_KEY);
    $this->wp->setTransient($key, $data, self::EXPIRATION);
  }

  /**
   * Stores the data of a subscription form. Keys of the registration CAPTCHA page are dropped,
   * so the stash can never be shown as a registration page.
   */
  public function setSubscriptionFormData(string $sessionId, array $data): void {
    foreach (self::REGISTER_ONLY_KEYS as $key) {
      unset($data[$key]);
    }
    $this->setFormData($sessionId, $data);
  }

  public function getFormData(string $sessionId) {
    if (!$this->isValidId($sessionId)) {
      return false;
    }
    $key = $this->getKey($sessionId, self::SESSION_FORM_KEY);
    return $this->wp->getTransient($key);
  }

  /**
   * @param mixed $hash
   */
  public function setCaptchaHash(string $sessionId, $hash): void {
    if (!$this->isValidId($sessionId)) {
      return;
    }
    $key = $this->getKey($sessionId, self::SESSION_HASH_KEY);
    $this->wp->setTransient($key, $hash, self::EXPIRATION);
  }

  public function getCaptchaHash(string $sessionId) {
    if (!$this->isValidId($sessionId)) {
      return false;
    }
    $key = $this->getKey($sessionId, self::SESSION_HASH_KEY);
    return $this->wp->getTransient($key);
  }

  /**
   * Counts a new session against its source: an IPv6 address counts by its /64 network, an IPv4-mapped
   * IPv6 address by its IPv4 address. Each source has a fixed window that starts with its first session.
   * Call it where a challenge is served or a stash is created for a visitor. The read-modify-write is
   * not atomic, so the limit is best-effort.
   *
   * @throws CaptchaSessionLimitException
   */
  public function registerNewSession(): void {
    $ip = Helpers::getIP();
    if (empty($ip)) {
      return;
    }

    $packed = inet_pton($ip);
    if ($packed === false) {
      $source = $ip;
    } elseif (strlen($packed) === 16) {
      $isIpv4Mapped = substr($packed, 0, 12) === str_repeat("\0", 10) . "\xff\xff";
      $source = $isIpv4Mapped ? substr($packed, 12) : substr($packed, 0, 8);
    } else {
      $source = $packed;
    }
    $key = 'MAILPOET_captcha_sessions_' . md5($source);

    $limit = $this->wp->applyFilters('mailpoet_captcha_session_limit', self::NEW_SESSION_LIMIT);
    $limit = is_numeric($limit) ? (int)$limit : self::NEW_SESSION_LIMIT;
    $now = time();
    $window = $this->wp->getTransient($key);
    $isOpen = is_array($window)
      && isset($window['count'], $window['expires'])
      && is_int($window['count'])
      && is_int($window['expires'])
      && $window['expires'] > $now;
    if (!$isOpen) {
      $window = ['count' => 0, 'expires' => $now + self::NEW_SESSION_WINDOW];
    }
    if ($window['count'] >= $limit) {
      if (empty($window['limit_logged'])) {
        $window['limit_logged'] = true;
        $this->wp->setTransient($key, $window, max(1, $window['expires'] - $now));
        $this->loggerFactory->getLogger(LoggerFactory::TOPIC_CAPTCHA)->error(
          'The CAPTCHA session limit was reached for a source. Sites behind a proxy can raise it with the mailpoet_captcha_session_limit filter.'
        );
      }
      throw new CaptchaSessionLimitException();
    }
    $window['count']++;
    $this->wp->setTransient($key, $window, max(1, $window['expires'] - $now));
  }

  private function getKey(string $sessionId, string $type): string {
    return implode('_', ['MAILPOET', $sessionId, $type]);
  }
}
