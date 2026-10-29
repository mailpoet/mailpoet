<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter\Renderer;

class EscapeHelper {
  /**
   * @param string $string
   * @return string
   */
  public static function escapeHtmlText($string) {
    return htmlspecialchars((string)$string, ENT_NOQUOTES, 'UTF-8');
  }

  /**
   * @param string $string
   * @return string
   */
  public static function escapeHtmlAttr($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
  }

  /**
   * Escapes Style attributes, but preserves single quotes. Some email clients
   * (e.g. Yahoo webmail) don't support encoded quoted font names.
   * Previously used htmlspecialchars but switched to esc_attr which is more appropriate.
   * @param string $string
   * @return string
   */
  public static function escapeHtmlStyleAttr($string) {
    return str_replace('&#039;', "'", esc_attr((string)$string));
  }

  /**
   * @param string $string
   * @return string
   */
  public static function unescapeHtmlStyleAttr($string) {
    // This decodes entities which may have been added by esc_attr.
    return htmlspecialchars_decode((string)$string, ENT_QUOTES);
  }

  /**
   * @param string $string
   * @return string
   */
  public static function escapeHtmlLinkAttr($string) {
    if (self::hasDisallowedScheme((string)$string)) {
      return '';
    }
    return self::escapeHtmlAttr($string);
  }

  /**
   * Whether the URL uses a scheme that is not allowed in links.
   */
  public static function hasDisallowedScheme(string $url): bool {
    // Matches "&#" followed by decimal digits or "x" and hex digits, with an optional ";"
    $url = (string)preg_replace_callback('/&#(x[0-9a-f]+|[0-9]+);?/i', function (array $reference): string {
      $code = strtolower($reference[1][0]) === 'x' ? (int)hexdec(substr($reference[1], 1)) : (int)$reference[1];
      return ($code > 0 && $code < 0x80) ? chr($code) : '&#' . $reference[1] . ';';
    }, $url);
    $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Browsers remove tabs and line breaks anywhere in a URL, so do the same before looking for the scheme.
    $url = str_replace(["\t", "\n", "\r"], '', $url);
    // The scheme starts the URL, after optional leading control characters and spaces (\x00-\x20)
    return preg_match('/^[\x00-\x20]*(javascript:|vbscript:|data:text|data:application)/ui', $url) === 1;
  }
}
