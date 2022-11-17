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
   * Similar to escapeHtmlAttr just this one keeps single quotes since some email clients
   * (e.g. Yahoo webmail) don't support encoded quoted font names
   * @param string $string
   * @return string
   */
  public static function escapeHtmlStyleAttr($string) {
    return htmlspecialchars((string)$string, ENT_COMPAT, 'UTF-8');
  }

  /**
   * @param string $string
   * @return string
   */
  public static function unescapeHtmlStyleAttr($string) {
    return htmlspecialchars_decode((string)$string, ENT_COMPAT);
  }

  /**
   * @param string $string
   * @return string
   */
  public static function escapeHtmlLinkAttr($string) {
    $string = self::escapeHtmlAttr($string);
    if (preg_match('/\s*(javascript:|data:text|data:application)/ui', $string) === 1) {
      return '';
    }
    return $string;
  }
}
