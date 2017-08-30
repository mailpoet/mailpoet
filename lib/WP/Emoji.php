<?php
namespace MailPoet\WP;

class Emoji {
  static function encodeForUTF8Column($table, $field, $value) {
    global $wpdb;
    $charset = $wpdb->get_col_charset($table, $field);
    if($charset === 'utf8') {
      $value = wp_encode_emoji($value);
    }
    return $value;
  }

  static function decodeEntities($content) {
    // Based on wp_staticize_emoji()

    // Loosely match the Emoji Unicode range.
    $regex = '/(&#x[2-3][0-9a-f]{3};|&#x1f[1-6][0-9a-f]{2};)/';

    $matches = array();
    if(preg_match_all($regex, $content, $matches)) {
      if(!empty($matches[1])) {
        foreach($matches[1] as $emoji) {
          $entity = html_entity_decode($emoji, ENT_COMPAT, 'UTF-8');
          $content = str_replace($emoji, $entity, $content);
        }
      }
    }

    return $content;
  }
}
