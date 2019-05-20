<?php
namespace MailPoet\WP;
use MailPoet\WP\Functions as WPFunctions;

class Emoji {
  private $wp;

  function __construct(WPFunctions $wp = null) {
    if ($wp === null) {
      $wp = new WPFunctions();
    }
    $this->wp = $wp;
  }

  function encodeForUTF8Column($table, $field, $value) {
    global $wpdb;
    $charset = $wpdb->get_col_charset($table, $field);
    if ($charset === 'utf8') {
      $value = $this->wp->wpEncodeEmoji($value);
    }
    return $value;
  }

  function decodeEntities($content) {
    // Based on WPFunctions::get()->wpStaticizeEmoji()

    // Loosely match the Emoji Unicode range.
    $regex = '/(&#x[2-3][0-9a-f]{3};|&#x1f[1-6][0-9a-f]{2};)/';

    $matches = [];
    if (preg_match_all($regex, $content, $matches)) {
      if (!empty($matches[1])) {
        foreach ($matches[1] as $emoji) {
          $entity = html_entity_decode($emoji, ENT_COMPAT, 'UTF-8');
          $content = str_replace($emoji, $entity, $content);
        }
      }
    }

    return $content;
  }
}
