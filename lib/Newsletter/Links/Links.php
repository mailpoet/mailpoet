<?php
namespace MailPoet\Newsletter\Links;

use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\Util\Security;

class Links {
  static function extract($text) {
    // adopted from WP's wp_extract_urls() function &  modified to work on hrefs
      # match href=' or href="
    $regex = '#(?:href.*?=.*?)(["\']?)('
      # match http://
      . '(?:([\w-]+:)?//?)'
      # match everything except for special characters # until .
      . '[^\s()<>]+'
      . '[.]'
      # conditionally match everything except for special characters after .
      . '(?:'
      . '\([\w\d]+\)|'
      . '(?:'
      . '[^`!()\[\]{};:\'".,<>«»“”‘’\s]|'
      . '(?:[:]\d+)?/?'
      . ')+'
      . ')'
      . ')\\1#';
    preg_match_all($regex, $text, $links);
    $shortcodes = new Shortcodes();;
    $shortcodes = $shortcodes->extract($text);
    return array_merge(
      array_unique($links[2]),
      $shortcodes
    );
  }

  static function replace($text, $links = false) {
    $links = ($links) ? $links : self::extract($text);
    $processed_links = array();
    foreach($links as $link) {
      $hash = Security::generateRandomString(5);
      $processed_links[] = array(
        'hash' => $hash,
        'url' => $link
      );
      $encoded_link = sprintf(
        '%s/?mailpoet&endpoint=track&action=click&data=%s',
        home_url(),
        '[mailpoet_data]-'.$hash
      );
      $link_regex = '/' . preg_quote($link, '/') . '/';
      $text = preg_replace($link_regex, $encoded_link, $text);
    }
    return array($text, $processed_links);
  }
}