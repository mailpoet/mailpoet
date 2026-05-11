<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Util;

use InvalidArgumentException;

class Cookies {
  const DEFAULT_OPTIONS = [
    'expires' => 0,
    'path' => '',
    'domain' => '',
    'secure' => false,
    'httponly' => false,
  ];

  public function set($name, $value, array $options = []) {
    if ($this->headersSent()) {
      return;
    }
    $options = $options + self::DEFAULT_OPTIONS;
    $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $error = json_last_error();
    if ($error || ($value === false)) {
      throw new InvalidArgumentException('Failed to JSON-encode cookie value');
    }

    $this->setCookie($name, $value, $options);
  }

  public function get($name) {
    if (!array_key_exists($name, $_COOKIE) || !is_string($_COOKIE[$name])) {
      return null;
    }
    $value = json_decode(sanitize_text_field(wp_unslash($_COOKIE[$name])), true);
    $error = json_last_error();
    if ($error) {
      return null;
    }
    return $value;
  }

  public function delete($name, array $options = []) {
    if (!$this->headersSent()) {
      $options = $options + self::DEFAULT_OPTIONS;
      $options['expires'] = time() - 3600;
      $this->setCookie($name, '', $options);

      if ($options['path'] === '') {
        $options['path'] = '/';
        $this->setCookie($name, '', $options);
      }
    }

    unset($_COOKIE[$name]);
  }

  protected function headersSent(): bool {
    return headers_sent();
  }

  protected function setCookie($name, string $value, array $options): void {
    setcookie($name, $value, $options);
  }
}
