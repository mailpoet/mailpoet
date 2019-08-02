<?php

namespace MailPoet\Config;

use MailPoet\Util\Security;

class Session
{
  const COOKIE_NAME = 'MAILPOET_SESSION';
  const KEY_LENGTH = 32;
  const COOKIE_EXPIRATION = 84600; // day

  function getId() {
    return isset($_COOKIE[self::COOKIE_NAME]) ? $_COOKIE[self::COOKIE_NAME] : null;
  }

  function init() {
    $id = $this->getId() ?: Security::generateRandomString(self::KEY_LENGTH);
    if (!headers_sent()) {
      $this->setCookie($id);
    }
  }

  function destroy() {
    if ($this->getId() === null) {
      return;
    }
    unset($_COOKIE[self::COOKIE_NAME]);
  }

  private function setCookie($id) {
    setcookie(
      self::COOKIE_NAME,
      $id,
      time() + self::COOKIE_EXPIRATION,
      "/"
    );
  }
}

