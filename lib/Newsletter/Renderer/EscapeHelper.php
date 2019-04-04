<?php
namespace MailPoet\Newsletter\Renderer;

class EscapeHelper {
  static function escapeHtmlText($string) {
    return htmlspecialchars((string)$string, ENT_NOQUOTES, 'UTF-8');
  }

  static function escapeHtmlAttr($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8', true);
  }

  static function escapeHtmlStyleAttr($string) {
    return htmlspecialchars((string)$string, ENT_COMPAT, 'UTF-8', true);
  }

  static function unescapeHtmlStyleAttr($string) {
    return htmlspecialchars_decode((string)$string, ENT_COMPAT);
  }

  static function escapeHtmlLinkAttr($string) {
    $string = self::escapeHtmlAttr($string);
    if (preg_match('~^javascript:|^data:text|^data:application~i', $string) === 1) {
      return '';
    }
    return $string;
  }
}
