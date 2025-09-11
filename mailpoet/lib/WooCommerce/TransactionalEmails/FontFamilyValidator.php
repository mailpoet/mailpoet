<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce\TransactionalEmails;

/**
 * Validator for font family values in CSS styles.
 */
class FontFamilyValidator {

  const DEFAULT_FONT_FAMILY = 'Arial, sans-serif';

  public function validateFontFamily(?string $fontFamily): string {
    if (empty($fontFamily)) {
      return self::DEFAULT_FONT_FAMILY;
    }

    $sanitized = $this->sanitizeFontFamily($fontFamily);

    if (empty($sanitized)) {
      return self::DEFAULT_FONT_FAMILY;
    }

    return $sanitized;
  }

  private function sanitizeFontFamily(string $fontFamily): string {
    // Remove characters that could break CSS context
    $sanitized = str_replace([
      '"', "'", ';', '<', '>', '\\', '/', '(', ')', '{', '}', '[', ']',
      '=', '+', '*', '^', '$', '@', '!', '~', '`', '|', '#', '%', '&', '?', ':',
    ], '', $fontFamily);

    // Normalize whitespace
    $sanitized = preg_replace('/\s+/u', ' ', $sanitized);
    if ($sanitized === null) {
      return '';
    }
    
    $sanitized = preg_replace('/\s*,\s*/u', ', ', $sanitized);
    if ($sanitized === null) {
      return '';
    }

    return trim($sanitized);
  }
}
