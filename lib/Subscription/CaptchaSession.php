<?php

namespace MailPoet\Subscription;

use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;

class CaptchaSession {
  const EXPIRATION = 1800; // 30 minutes
  const ID_LENGTH = 32;

  const SESSION_HASH_KEY = 'hash';
  const SESSION_FORM_KEY = 'form';

  /** @var WPFunctions */
  private $wp;

  /** @var string */
  private $id;

  function __construct(WPFunctions $wp) {
    $this->wp = $wp;
  }

  function init($id = null) {
    $this->id = $id ?: Security::generateRandomString(self::ID_LENGTH);
  }

  function getId() {
    if ($this->id === null) {
      throw new \Exception("MailPoet captcha session not initialized.");
    }
    return $this->id;
  }

  function reset() {
    $this->wp->deleteTransient($this->getKey(self::SESSION_FORM_KEY));
    $this->wp->deleteTransient($this->getKey(self::SESSION_HASH_KEY));
  }

  function setFormData(array $data) {
    $this->wp->setTransient($this->getKey(self::SESSION_FORM_KEY), $data, self::EXPIRATION);
  }

  function getFormData() {
    return $this->wp->getTransient($this->getKey(self::SESSION_FORM_KEY));
  }

  function setCaptchaHash($hash) {
    $this->wp->setTransient($this->getKey(self::SESSION_HASH_KEY), $hash, self::EXPIRATION);
  }

  function getCaptchaHash() {
    return $this->wp->getTransient($this->getKey(self::SESSION_HASH_KEY));
  }

  private function getKey($type) {
    return implode('_', ['MAILPOET', $this->getId(), $type]);
  }
}
