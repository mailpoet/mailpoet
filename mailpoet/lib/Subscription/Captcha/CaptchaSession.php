<?php

namespace MailPoet\Subscription\Captcha;

use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;

class CaptchaSession {
  const EXPIRATION = 1800; // 30 minutes
  const ID_LENGTH = 32;

  const SESSION_HASH_KEY = 'hash';
  const SESSION_FORM_KEY = 'form';

  /** @var WPFunctions */
  private $wp;

  /** @var ?string */
  private $id = null;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function init($id = null) {
    $this->id = $id ?: Security::generateRandomString(self::ID_LENGTH);
  }

  public function getId(): ?string {
    return $this->id;
  }

  public function reset() {
    $this->wp->deleteTransient($this->getKey(self::SESSION_FORM_KEY));
    $this->wp->deleteTransient($this->getKey(self::SESSION_HASH_KEY));
  }

  public function setFormData(array $data) {
    $this->wp->setTransient($this->getKey(self::SESSION_FORM_KEY), $data, self::EXPIRATION);
  }

  public function getFormData() {
    return $this->wp->getTransient($this->getKey(self::SESSION_FORM_KEY));
  }

  public function setCaptchaHash($hash) {
    $this->wp->setTransient($this->getKey(self::SESSION_HASH_KEY), $hash, self::EXPIRATION);
  }

  public function getCaptchaHash() {
    return $this->wp->getTransient($this->getKey(self::SESSION_HASH_KEY));
  }

  private function getKey($type) {
    return \implode('_', ['MAILPOET', $this->getId(), $type]);
  }
}
