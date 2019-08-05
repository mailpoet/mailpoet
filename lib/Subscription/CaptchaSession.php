<?php

namespace MailPoet\Subscription;

use MailPoet\Config\Session;
use MailPoet\WP\Functions as WPFunctions;

class CaptchaSession {
  const EXPIRATION = 1800; // 30 minutes

  const SESSION_HASH_KEY = 'hash';
  const SESSION_FORM_KEY = 'form';

  /** @var WPFunctions */
  private $wp;

  /** @var Session */
  private $session;

  function __construct(WPFunctions $wp, Session $session) {
    $this->wp = $wp;
    $this->session = $session;
  }

  function isAvailable() {
    return $this->session->getId() !== null;
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
    $session_id = $this->session->getId();
    if ($session_id === null) {
      throw new \Exception("MailPoet session not initialized.");
    }
    return implode('_', ['MAILPOET', $session_id, $type]);
  }
}
