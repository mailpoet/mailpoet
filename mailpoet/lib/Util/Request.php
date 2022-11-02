<?php

namespace MailPoet\Util;

class Request {
  public function isPost(): bool {
    return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' || count($_POST) > 0;
  }
}
