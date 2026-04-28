<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Util;

class Request {
  public function isPost(): bool {
    return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' || count($_POST) > 0;
  }

  public function getStringParam(string $key): ?string {
    if ($this->isPost()) {
      return $this->getSanitizedParam($_POST, $key, 'sanitize_text_field');
    }
    return $this->getSanitizedParam($_GET, $key, 'sanitize_text_field');
  }

  public function getTextareaParam(string $key): ?string {
    if ($this->isPost()) {
      return $this->getSanitizedParam($_POST, $key, 'sanitize_textarea_field');
    }
    return $this->getSanitizedParam($_GET, $key, 'sanitize_textarea_field');
  }

  private function getSanitizedParam(array $source, string $key, callable $sanitize): ?string {
    if (!isset($source[$key]) || !is_scalar($source[$key])) {
      return null;
    }
    return $sanitize(wp_unslash((string)$source[$key]));
  }
}
