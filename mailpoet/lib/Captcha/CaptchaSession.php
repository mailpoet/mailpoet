<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

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

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
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

  public function deleteCaptchaHash(string $sessionId): void {
    if (!$this->isValidId($sessionId)) {
      return;
    }
    $this->wp->deleteTransient($this->getKey($sessionId, self::SESSION_HASH_KEY));
  }

  /**
   * @throws CaptchaSessionLimitException
   */
  public function setFormData(string $sessionId, array $data): void {
    if (!$this->isValidId($sessionId)) {
      return;
    }
    $this->registerNewSession($sessionId);
    $key = $this->getKey($sessionId, self::SESSION_FORM_KEY);
    $this->wp->setTransient($key, $data, self::EXPIRATION);
  }

  /**
   * Stores the data of a subscription form. Keys of the registration CAPTCHA page are dropped,
   * so the stash can never be shown as a registration page.
   *
   * @throws CaptchaSessionLimitException
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
   * @throws CaptchaSessionLimitException
   */
  public function setCaptchaHash(string $sessionId, $hash): void {
    if (!$this->isValidId($sessionId)) {
      return;
    }
    $this->registerNewSession($sessionId);
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
   * Counts each new session against its source (an IPv6 address counts by its /64 network).
   * The read-modify-write is not atomic, so the limit is best-effort.
   *
   * @throws CaptchaSessionLimitException
   */
  private function registerNewSession(string $sessionId): void {
    if ($this->exists($sessionId)) {
      return;
    }
    $ip = Helpers::getIP();
    if (empty($ip)) {
      return;
    }

    $packed = inet_pton($ip);
    if ($packed === false) {
      $source = $ip;
    } else {
      $source = strlen($packed) === 16 ? substr($packed, 0, 8) : $packed;
    }
    $key = 'MAILPOET_captcha_sessions_' . md5($source);

    $limit = $this->wp->applyFilters('mailpoet_captcha_session_limit', self::NEW_SESSION_LIMIT);
    $limit = is_numeric($limit) ? (int)$limit : self::NEW_SESSION_LIMIT;
    $count = $this->wp->getTransient($key);
    $count = is_numeric($count) ? (int)$count : 0;
    if ($count >= $limit) {
      throw new CaptchaSessionLimitException('Too many CAPTCHA sessions.');
    }
    $this->wp->setTransient($key, $count + 1, self::NEW_SESSION_WINDOW);
  }

  private function getKey(string $sessionId, string $type): string {
    return implode('_', ['MAILPOET', $sessionId, $type]);
  }
}
