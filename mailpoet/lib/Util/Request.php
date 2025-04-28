<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Util;

class Request {
  public function isPost(): bool {
    return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' || count($_POST) > 0;
  }

  public function getStringParam(string $key): ?string {
    if ($this->isPost()) {
      return isset($_POST[$key]) ? sanitize_text_field($_POST[$key]) : null;
    }
    return isset($_GET[$key]) ? sanitize_text_field($_GET[$key]) : null;
  }
}
