<?php

namespace MailPoet\Config;

use MailPoet\Util\Cookies;
use MailPoet\Util\Security;

class Session
{
  const COOKIE_NAME = 'MAILPOET_SESSION';
  const KEY_LENGTH = 32;
  const COOKIE_EXPIRATION = 84600; // day

  /** @var Cookies */
  private $cookies;

  function __construct(Cookies $cookies) {
    $this->cookies = $cookies;
  }

  function getId() {
    return $this->cookies->get(self::COOKIE_NAME) ?: null;
  }

  function init() {
    if (headers_sent()) {
      return false;
    }
    $id = $this->getId() ?: Security::generateRandomString(self::KEY_LENGTH);
    $this->setCookie($id);
    return true;
  }

  function destroy() {
    if ($this->getId() === null) {
      return;
    }
    $this->cookies->delete(self::COOKIE_NAME);
  }

  private function setCookie($id) {
    $this->cookies->set(
      self::COOKIE_NAME,
      $id,
      [
        'expires' => time() + self::COOKIE_EXPIRATION,
        'path' => '/',
      ]
    );
  }
}

