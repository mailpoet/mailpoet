<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;

class CaptchaSession {
  const EXPIRATION = 1800; // 30 minutes
  const ID_LENGTH = 32;

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

  public function reset(string $sessionId): void {
    $formKey = $this->getKey($sessionId, self::SESSION_FORM_KEY);
    $hashKey = $this->getKey($sessionId, self::SESSION_HASH_KEY);
    $this->wp->deleteTransient($formKey);
    $this->wp->deleteTransient($hashKey);
  }

  public function setFormData(string $sessionId, array $data): void {
    $key = $this->getKey($sessionId, self::SESSION_FORM_KEY);
    $this->wp->setTransient($key, $data, self::EXPIRATION);
  }

  public function getFormData(string $sessionId) {
    $key = $this->getKey($sessionId, self::SESSION_FORM_KEY);
    return $this->wp->getTransient($key);
  }

  public function setCaptchaHash(string $sessionId, $hash): void {
    $key = $this->getKey($sessionId, self::SESSION_HASH_KEY);
    $this->wp->setTransient($key, $hash, self::EXPIRATION);
  }

  public function getCaptchaHash(string $sessionId) {
    $key = $this->getKey($sessionId, self::SESSION_HASH_KEY);
    return $this->wp->getTransient($key);
  }

  private function getKey(string $sessionId, string $type): string {
    return implode('_', ['MAILPOET', $sessionId, $type]);
  }
}
